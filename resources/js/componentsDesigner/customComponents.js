import catalog from '../designer/public-screen-components.json';
import publicScreenComponentKey from './publicScreenComponents';

const modules = {
  ...import.meta.glob('../projects/packageSkeleton/**/*.vue', { eager: true }),
};

const customComponents = [];
const officialComponents = [];

const TRUSTED_COMPONENT_KEY = /^(?:@\/|custom:\/\/)[A-Za-z0-9_./-]+$/;
const TRUSTED_COMPONENT_NAME = /^[A-Z][A-Za-z0-9]*$/;

function normalizeModule(component) {
  return component?.default ?? component;
}

export function isTrustedComponentKey(key) {
  return typeof key === 'string' && TRUSTED_COMPONENT_KEY.test(key);
}

function isTrustedComponentName(name) {
  return typeof name === 'string' && TRUSTED_COMPONENT_NAME.test(name);
}

function moduleForKey(key) {
  const relative = key.replace(/^@\//, '../');
  if (modules[relative]) return modules[relative];
  const suffix = key.replace(/^@\//, '');
  const match = Object.keys(modules).find((id) => id.endsWith(`/${suffix}`));
  return match ? modules[match] : null;
}

function replaceByKey(list, entry) {
  const index = list.findIndex((item) => item.key === entry.key);
  if (index === -1) list.push(entry);
  else list.splice(index, 1, entry);
}

export function registerPublicScreenComponent(name, component, uses = []) {
  const key = publicScreenComponentKey(name);
  replaceByKey(officialComponents, {
    name,
    key,
    component: normalizeModule(component),
    components: Array.isArray(uses) ? uses : [],
  });
}

function registerCatalogEntries(entries) {
  entries.forEach(({ name, key, components: uses }) => {
    const mod = moduleForKey(key);
    if (!mod) {
      throw new Error(`No SFC bundled for ${key}`);
    }
    registerPublicScreenComponent(name, mod.default ?? mod, uses || []);
  });
}

export function registerPublicScreenCatalog() {
  registerCatalogEntries(catalog);
}

export function registerCustomComponent(record) {
  if (!record || !isTrustedComponentKey(record.key)) return;
  replaceByKey(customComponents, {
    name: record.name,
    key: record.key,
    template: record.template || '',
    script: record.script || '',
    style: record.style || '',
    components: Array.isArray(record.components) ? record.components : [],
    factory: typeof record.factory === 'function' ? record.factory : null,
  });
}

export function registerCustomComponents(records = []) {
  records.forEach(registerCustomComponent);
}

export function removeCustomComponentStyle(key) {
  document.querySelectorAll('style[data-custom-vue-component]').forEach((node) => {
    if (node.dataset.customVueComponent === key) node.remove();
  });
}

function injectStyle(key, css) {
  removeCustomComponentStyle(key);
  if (!css) return;
  const style = document.createElement('style');
  style.dataset.customVueComponent = key;
  style.textContent = css;
  document.head.appendChild(style);
}

export function createComponent(key, definition) {
  let options = {};
  if (typeof definition.factory === 'function') {
    options = definition.factory() || {};
  } else if (definition.script && isTrustedComponentKey(key)) {
    // Administrator-authored function body from the component registry, not URL or request input.
    // eslint-disable-next-line no-new-func
    options = new Function(definition.script)() || {}; // NOSONAR
  }
  injectStyle(key, definition.style);
  return {
    ...options,
    name: definition.name || options.name,
    template: definition.template,
    components: { ...options.components },
  };
}

export function resolveCustomComponents(uses = [], visited = new Set()) {
  return uses.reduce((components, entry = {}) => {
    const { name, key } = entry;
    if (!isTrustedComponentName(name) || !isTrustedComponentKey(key) || visited.has(key)) {
      return components;
    }
    const nextVisited = new Set([...visited, key]);

    const custom = customComponents.find((item) => item.key === key);
    if (custom) {
      const component = createComponent(key, custom);
      component.components = {
        ...component.components,
        ...resolveCustomComponents(custom.components, nextVisited),
      };
      components[name] = component;
      return components;
    }

    const official = officialComponents.find((item) => item.key === key);
    if (!official) return components;
    const children = resolveCustomComponents(official.components || [], nextVisited);
    components[name] = Object.keys(children).length
      ? { extends: official.component, components: children }
      : official.component;
    return components;
  }, {});
}

export function resolveDeclaredComponents(uses = []) {
  const seen = new Set();
  const declared = [];
  uses.forEach((entry = {}) => {
    const { name, key } = entry;
    if (!isTrustedComponentName(name) || !isTrustedComponentKey(key) || seen.has(key)) return;
    seen.add(key);

    const custom = customComponents.find((item) => item.key === key);
    if (custom) {
      declared.push({
        name,
        component: createComponent(key, custom),
        custom: true,
        uses: Array.isArray(custom.components) ? custom.components : [],
      });
      return;
    }
    const official = officialComponents.find((item) => item.key === key);
    if (official) {
      declared.push({
        name,
        component: official.component,
        custom: false,
        uses: Array.isArray(official.components) ? official.components : [],
      });
    }
  });

  const names = new Set(declared.map((entry) => entry.name));
  const resolved = {};
  declared.forEach(({
    name, component, custom, uses: ownUses,
  }) => {
    const hasDeclaredUse = ownUses.some((use) => (
      use?.name && use.name !== name && names.has(use.name)
    ));
    if (custom || !hasDeclaredUse) {
      resolved[name] = component;
      return;
    }
    resolved[name] = { extends: component, components: {} };
  });

  declared.forEach(({ name, custom, uses: ownUses }) => {
    const wired = {};
    ownUses.forEach((use = {}) => {
      const useName = use.name;
      if (!useName || useName === name || !resolved[useName]) return;
      wired[useName] = resolved[useName];
    });
    if (!Object.keys(wired).length) return;
    const target = resolved[name];
    if (custom) {
      target.components = { ...target.components, ...wired };
      return;
    }
    target.components = wired;
  });

  return resolved;
}

let screenComponentRegistry = null;
let screenComponentRegistryReady = false;

export function ensureScreenComponentRegistry() {
  if (screenComponentRegistry) return screenComponentRegistry;

  registerPublicScreenCatalog();
  screenComponentRegistryReady = true;
  screenComponentRegistry = Promise.resolve();
  return screenComponentRegistry;
}

export function isScreenComponentRegistryReady() {
  return screenComponentRegistryReady;
}

window.ProcessMakerCustomComponents = {
  register(key, definition = {}) {
    registerCustomComponent({
      key,
      name: definition.name,
      template: definition.template,
      style: definition.style,
      components: definition.components,
      factory: definition.factory,
    });
  },
};
