export type TokenCategory = 'core' | 'semantic' | 'component';

export interface CoreTokenGroup {
  color: Record<string, string>;
  typography: {
    font: Record<string, string>;
    scale: Record<string, number>;
    weight: Record<string, number>;
    lineHeight: Record<string, number>;
    tracking: Record<string, number>;
  };
  space: {
    scale: Record<string, number>;
  };
  shape: {
    radius: Record<string, string>;
    borderWidth: Record<string, string>;
  };
  motion: {
    duration: Record<string, number>;
    easing: Record<string, string>;
  };
  elevation: {
    shadow: Record<string, string>;
    zIndex: Record<string, number>;
  };
}

export interface SemanticTokenGroup {
  surface: Record<string, string>;
  text: Record<string, string>;
  accent: Record<string, string>;
  state: Record<string, string>;
  density: Record<string, number>;
  focus: Record<string, string>;
  divider: Record<string, string>;
}

export interface ComponentTokenGroup {
  [componentName: string]: Record<string, string | number>;
}

export interface TokenBundle {
  core: CoreTokenGroup;
  semantic: SemanticTokenGroup;
  component: ComponentTokenGroup;
}

