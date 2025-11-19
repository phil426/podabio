import { useState } from 'react';
import { ColorTokenPicker } from '../controls/ColorTokenPicker';
import { PageBackgroundPicker } from '../controls/PageBackgroundPicker';
import styles from './color-picker-demo.module.css';

// Demo page that doesn't require tokens - works standalone

export function ColorPickerDemo(): JSX.Element {
  const [option1Value, setOption1Value] = useState('#FFFFFF');
  const [option2Value, setOption2Value] = useState('#FFFFFF');
  const [option3Value, setOption3Value] = useState('#FFFFFF');

  return (
    <div className={styles.container}>
      <header className={styles.header}>
        <h1>Color Picker Component Options</h1>
        <p>Demo page showing different implementations for Page Background color picker (Solid & Gradient)</p>
      </header>

      <div className={styles.content}>
        {/* Option 1: ColorTokenPicker - Compact with Toggle */}
        <section className={styles.section}>
          <div className={styles.sectionHeader}>
            <h2>Option 1: Compact Picker with Mode Toggle</h2>
            <p className={styles.description}>
              <strong>ColorTokenPicker</strong> - A compact, space-efficient picker with a built-in solid/gradient toggle. 
              Features a swatch button that expands to show a full color picker, HEX/RGB/HSL inputs, and a complete gradient editor. 
              Perfect for token-based theming systems where you need both solid colors and gradients in a minimal UI.
            </p>
            <div className={styles.codeBlock}>
              <code>
                {`<ColorTokenPicker
  label="Page Background"
  token="semantic.surface.canvas"
  value={value}
  onChange={setValue}
  hideToken
/>`}
              </code>
            </div>
          </div>
          <div className={styles.pickerContainer}>
            <ColorTokenPicker
              label="Page Background"
              token="semantic.surface.canvas"
              value={option1Value}
              onChange={setOption1Value}
              hideToken
            />
          </div>
          <div className={styles.valueDisplay}>
            <strong>Current Value:</strong> <code>{option1Value}</code>
          </div>
          <div className={styles.prosCons}>
            <div className={styles.pros}>
              <strong>Pros:</strong>
              <ul>
                <li>Compact, space-efficient design</li>
                <li>Built-in solid/gradient toggle</li>
                <li>Shows HEX, RGB, and HSL values</li>
                <li>Full gradient editor with direction control</li>
                <li>Token-based integration</li>
              </ul>
            </div>
            <div className={styles.cons}>
              <strong>Cons:</strong>
              <ul>
                <li>No preset colors/gradients</li>
                <li>Requires expansion to see picker</li>
              </ul>
            </div>
          </div>
        </section>

        {/* Option 2: PageBackgroundPicker - Full Featured with Presets */}
        <section className={styles.section}>
          <div className={styles.sectionHeader}>
            <h2>Option 2: Full-Featured Picker with Presets</h2>
            <p className={styles.description}>
              <strong>PageBackgroundPicker</strong> - A comprehensive picker with preset color and gradient grids, 
              plus custom color/gradient editors. Shows both solid and gradient sections side-by-side. 
              Ideal for page background editing where users benefit from quick preset selection and full customization options.
            </p>
            <div className={styles.codeBlock}>
              <code>
                {`<PageBackgroundPicker
  value={value}
  onChange={setValue}
  mode="both"
/>`}
              </code>
            </div>
          </div>
          <div className={styles.pickerContainer}>
            <PageBackgroundPicker
              value={option2Value}
              onChange={setOption2Value}
              mode="both"
            />
          </div>
          <div className={styles.valueDisplay}>
            <strong>Current Value:</strong> <code>{option2Value}</code>
          </div>
          <div className={styles.prosCons}>
            <div className={styles.pros}>
              <strong>Pros:</strong>
              <ul>
                <li>24 preset colors (light & dark)</li>
                <li>12 preset gradients</li>
                <li>Custom color and gradient editors</li>
                <li>All options visible at once</li>
                <li>Great for quick selection</li>
              </ul>
            </div>
            <div className={styles.cons}>
              <strong>Cons:</strong>
              <ul>
                <li>Larger footprint</li>
                <li>No HEX/RGB/HSL display</li>
                <li>More scrolling required</li>
              </ul>
            </div>
          </div>
        </section>

        {/* Option 3: PageBackgroundPicker - Mode-Specific */}
        <section className={styles.section}>
          <div className={styles.sectionHeader}>
            <h2>Option 3: Mode-Specific Picker</h2>
            <p className={styles.description}>
              <strong>PageBackgroundPicker</strong> with <code>mode="solid"</code> or <code>mode="gradient"</code>. 
              Shows only the relevant section based on your needs. Use <code>mode="solid"</code> when you only need solid colors, 
              or <code>mode="gradient"</code> when you only need gradients. Reduces UI complexity and focuses the user's attention.
            </p>
            <div className={styles.codeBlock}>
              <code>
                {`// For solid colors only:
<PageBackgroundPicker
  value={value}
  onChange={setValue}
  mode="solid"
/>

// For gradients only:
<PageBackgroundPicker
  value={value}
  onChange={setValue}
  mode="gradient"
/>`}
              </code>
            </div>
          </div>
          <div className={styles.pickerContainer}>
            <div style={{ marginBottom: '2rem' }}>
              <h3 style={{ fontSize: '1rem', marginBottom: '0.5rem', color: 'var(--color-text-primary, #111827)' }}>Solid Mode:</h3>
              <PageBackgroundPicker
                value={option3Value}
                onChange={setOption3Value}
                mode="solid"
              />
            </div>
            <div>
              <h3 style={{ fontSize: '1rem', marginBottom: '0.5rem', color: 'var(--color-text-primary, #111827)' }}>Gradient Mode:</h3>
              <PageBackgroundPicker
                value={option3Value}
                onChange={setOption3Value}
                mode="gradient"
              />
            </div>
          </div>
          <div className={styles.valueDisplay}>
            <strong>Current Value:</strong> <code>{option3Value}</code>
          </div>
          <div className={styles.prosCons}>
            <div className={styles.pros}>
              <strong>Pros:</strong>
              <ul>
                <li>Focused, simplified UI</li>
                <li>Reduces cognitive load</li>
                <li>Still includes presets</li>
                <li>Smaller footprint than "both" mode</li>
                <li>Good for single-purpose use cases</li>
              </ul>
            </div>
            <div className={styles.cons}>
              <strong>Cons:</strong>
              <ul>
                <li>Can't switch modes in UI</li>
                <li>Requires knowing which mode you need</li>
                <li>Less flexible than toggle-based approach</li>
              </ul>
            </div>
          </div>
        </section>

        {/* Recommendation */}
        <section className={styles.section}>
          <div className={styles.sectionHeader}>
            <h2>Recommendation</h2>
            <div className={styles.recommendation}>
              <p>
                <strong>For Lefty's Colors Panel:</strong> Use <strong>Option 1 (ColorTokenPicker)</strong> because:
              </p>
              <ul>
                <li>It's compact and fits well in the left rail content panel</li>
                <li>The solid/gradient toggle is intuitive for users</li>
                <li>HEX/RGB/HSL display is helpful for advanced users</li>
                <li>Works seamlessly with your token-based theming system</li>
                <li>The expandable design keeps the UI clean when not in use</li>
              </ul>
              <p>
                <strong>Alternative:</strong> If you want preset colors for faster selection, use <strong>Option 2 (PageBackgroundPicker)</strong> 
                but be aware it will take more vertical space in your panel.
              </p>
            </div>
          </div>
        </section>
      </div>
    </div>
  );
}

