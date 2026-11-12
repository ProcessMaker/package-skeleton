import components from '../designer/public-screen-components.json';

export default function publicScreenComponentKey(name) {
  const definition = components.find((component) => component.name === name);

  if (!definition) {
    throw new Error(
      `Public screen component "${name}" must be registered in public-screen-components.json`,
    );
  }

  return definition.key;
}
