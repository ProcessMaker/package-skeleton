// Keep the host's grouping for other controls; only merge this control's panels.
export default function mergeVueComponentInspectorPanels(manager) {
  const builder = manager.$refs?.builder;
  if (!builder || typeof builder.getInspectorFields !== 'function' || builder.plgVueInspectorMerged) return;

  const original = builder.getInspectorFields.bind(builder);
  builder.getInspectorFields = (accordion) => {
    if (builder.inspection?.component !== 'VueComponentRenderTemplate') return original(accordion);
    if (accordion.name === 'Variable') return [];
    if (accordion.name !== 'Configuration') return original(accordion);

    const variable = builder.accordions.find((panel) => panel.name === 'Variable');
    return [...(variable ? original(variable) : []), ...original(accordion)];
  };
  builder.plgVueInspectorMerged = true;
}
