import type { ComponentTokenGroup } from './TokenTypes';

export const componentTokensLight: ComponentTokenGroup = {
  'layout.topbar': {
    height: '64px',
    background: 'semantic.surface.panel',
    borderBottom: 'semantic.divider.subtle',
    paddingInline: 'space.scale.md',
    titleColor: 'semantic.text.primary'
  },
  'layout.left-rail': {
    minWidth: '280px',
    background: 'semantic.surface.panel',
    borderRight: 'semantic.divider.subtle'
  },
  'layout.canvas': {
    background: 'semantic.surface.canvas'
  },
  'layout.properties': {
    minWidth: '340px',
    background: 'semantic.surface.panel',
    borderLeft: 'semantic.divider.subtle'
  },
  'panel.drawer': {
    background: 'semantic.surface.panel',
    shadow: 'core.elevation.shadow.level2'
  },
  'button.primary': {
    background: 'semantic.accent.primary',
    color: 'semantic.text.inverse',
    radius: 'core.shape.radius.md'
  },
  'button.ghost': {
    color: 'semantic.text.primary',
    radius: 'core.shape.radius.md'
  },
  'list.layer.item': {
    gap: 'space.scale.sm',
    radius: 'core.shape.radius.sm',
    paddingBlock: 'space.scale.xs',
    paddingInline: 'space.scale.sm'
  }
};
