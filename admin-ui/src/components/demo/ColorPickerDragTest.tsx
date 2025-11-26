/**
 * Color Picker Drag Test
 * Isolated test page to debug dragging issues
 * Access at: /admin/react-admin.php?page=color-picker-test
 */

import { useState } from 'react';
import { HexColorPicker } from 'react-colorful';
import * as Slider from '@radix-ui/react-slider';
import * as Popover from '@radix-ui/react-popover';
import ColorPicker from 'react-best-gradient-color-picker';
import { BackgroundColorSwatch } from '../controls/BackgroundColorSwatch';
import { PodaColorPicker } from './PodaColorPicker';
import styles from './color-picker-drag-test.module.css';
import chipStyles from './color-chip-test.module.css';

export function ColorPickerDragTest(): JSX.Element {
  const [backgroundValue, setBackgroundValue] = useState('linear-gradient(135deg, #6366f1 0%, #4f46e5 100%)');
  const [backgroundType, setBackgroundType] = useState<'solid' | 'gradient'>('gradient');
  const [testColor, setTestColor] = useState('#6366f1');
  const [testSlider, setTestSlider] = useState(135);
  const [customGradientValue, setCustomGradientValue] = useState('linear-gradient(135deg, #6366f1 0%, #4f46e5 100%)');
  const [chipGradientValue, setChipGradientValue] = useState('linear-gradient(135deg, #6366f1 0%, #4f46e5 100%)');
  const [chipOpen, setChipOpen] = useState(false);

  return (
    <div 
      style={{
        position: 'absolute',
        top: 0,
        left: 0,
        right: 0,
        bottom: 0,
        overflowY: 'auto',
        overflowX: 'hidden'
      }}
    >
      <div className={styles.container}>
      <div className={styles.header}>
        <h1>Color Picker Drag Test</h1>
        <p>Test dragging in color picker and gradient slider outside of Dialog context</p>
      </div>

      <div className={styles.testSection}>
        <h2>Test 0: Direct HexColorPicker (No Wrappers)</h2>
        <p>Testing react-colorful directly with no wrappers, Popovers, or custom CSS.</p>
        <div className={styles.testBox}>
          <label>Direct Color Picker:</label>
          <HexColorPicker
            color={testColor}
            onChange={setTestColor}
          />
          <div style={{ marginTop: '1rem', fontFamily: 'monospace' }}>
            Color: {testColor}
          </div>
        </div>
      </div>

      <div className={styles.testSection}>
        <h2>Test 1: Direct Radix Slider (No Wrappers)</h2>
        <p>Testing Radix Slider directly with no wrappers or custom CSS.</p>
        <div className={styles.testBox}>
          <label>Direct Slider:</label>
          <Slider.Root
            value={[testSlider]}
            onValueChange={(values) => setTestSlider(values[0])}
            min={0}
            max={360}
            step={1}
            style={{ 
              position: 'relative',
              display: 'flex',
              alignItems: 'center',
              width: '100%',
              minWidth: '120px',
              height: '20px',
              userSelect: 'none'
            }}
          >
            <Slider.Track style={{ 
              position: 'relative', 
              flex: 1, 
              height: '6px', 
              background: 'rgba(0,0,0,0.1)', 
              borderRadius: '3px',
              alignSelf: 'center'
            }}>
              <Slider.Range style={{ 
                position: 'absolute', 
                height: '100%', 
                background: '#2563eb', 
                borderRadius: '3px' 
              }} />
            </Slider.Track>
            <Slider.Thumb 
              style={{ 
                display: 'block', 
                width: '16px', 
                height: '16px', 
                background: '#2563eb', 
                borderRadius: '50%', 
                cursor: 'pointer',
                boxShadow: '0 2px 4px rgba(0, 0, 0, 0.1)'
                // Let Radix UI handle all positioning automatically - no manual positioning needed
              }}
              aria-label="Slider value"
            />
          </Slider.Root>
          <div style={{ marginTop: '1rem', fontFamily: 'monospace' }}>
            Value: {testSlider}°
          </div>
        </div>
      </div>

      <div className={styles.testSection}>
        <h2>Test 2a: Poda Color Picker (react-colorful + Radix Slider)</h2>
        <p>Poda Color - A compact gradient & solid color picker built with react-colorful and Radix Slider.</p>
        <div className={styles.testBox}>
          <label>Poda Color Picker:</label>
          <PodaColorPicker
            value={customGradientValue}
            onChange={setCustomGradientValue}
          />
          <div className={styles.valueDisplay} style={{ marginTop: '1rem' }}>
            <strong>Current Value:</strong> {customGradientValue}
          </div>
        </div>
      </div>

      <div className={styles.testSection}>
        <h2>Test 2a-2: Poda Color Picker from Color Chip</h2>
        <p>Same picker, but opens from a color chip (like BackgroundColorSwatch).</p>
        <div className={styles.testBox}>
          <label>Gradient Color Chip:</label>
          <Popover.Root open={chipOpen} onOpenChange={setChipOpen}>
            <Popover.Trigger asChild>
              <button
                type="button"
                className={chipStyles.colorChip}
                style={{ backgroundImage: chipGradientValue }}
                aria-expanded={chipOpen}
                aria-label="Open gradient picker"
              >
                <span className={chipStyles.chipSwatch} aria-hidden="true" />
              </button>
            </Popover.Trigger>
            <Popover.Portal>
              <Popover.Content
                className={chipStyles.popover}
                sideOffset={5}
                align="end"
              >
                <PodaColorPicker
                  value={chipGradientValue}
                  onChange={(value) => {
                    setChipGradientValue(value);
                  }}
                />
              </Popover.Content>
            </Popover.Portal>
          </Popover.Root>
          <div className={styles.valueDisplay} style={{ marginTop: '1rem' }}>
            <strong>Current Value:</strong> {chipGradientValue}
          </div>
        </div>
      </div>

      <div className={styles.testSection}>
        <h2>Test 2b: react-best-gradient-color-picker (No Wrappers)</h2>
        <p>Testing the alternative gradient color picker library directly with no wrappers.</p>
        <div className={styles.testBox}>
          <label>Alternative Gradient Picker:</label>
          <ColorPicker
            value={backgroundValue}
            onChange={setBackgroundValue}
          />
          <div className={styles.valueDisplay} style={{ marginTop: '1rem' }}>
            <strong>Current Value:</strong> {backgroundValue}
          </div>
        </div>
      </div>

      <div className={styles.testSection}>
        <h2>Test 2c: BackgroundColorSwatch with Gradient (No Dialog)</h2>
        <p>This is the EXACT same component used in theme editor (with Popover), but NOT inside a Dialog modal.</p>
        <p style={{ marginTop: '0.5rem', fontWeight: 600, color: '#2563eb' }}>
          ⚠️ Make sure to switch to <strong>Gradient</strong> mode to test the gradient color picker and direction slider!
        </p>
        <div className={styles.testBox}>
          <label>Background Color (Gradient Mode):</label>
          <BackgroundColorSwatch
            value={backgroundValue}
            backgroundType={backgroundType}
            onChange={(value) => {
              setBackgroundValue(value);
              console.log('Background changed:', value);
            }}
            onTypeChange={(type) => {
              setBackgroundType(type);
              if (type === 'solid') {
                setBackgroundValue('#6366f1');
              } else {
                setBackgroundValue('linear-gradient(135deg, #6366f1 0%, #4f46e5 100%)');
              }
            }}
            label="Test background"
          />
          <div style={{ marginTop: '1rem', display: 'flex', gap: '0.5rem', alignItems: 'center' }}>
            <button
              type="button"
              onClick={() => {
                setBackgroundType('gradient');
                setBackgroundValue('linear-gradient(135deg, #6366f1 0%, #4f46e5 100%)');
              }}
              style={{
                padding: '6px 12px',
                border: '1px solid #2563eb',
                borderRadius: '6px',
                background: backgroundType === 'gradient' ? '#2563eb' : 'white',
                color: backgroundType === 'gradient' ? 'white' : '#2563eb',
                cursor: 'pointer',
                fontSize: '13px',
                fontWeight: 500
              }}
            >
              Switch to Gradient
            </button>
            <span style={{ fontSize: '12px', color: '#64748b' }}>
              Current mode: <strong>{backgroundType}</strong>
            </span>
          </div>
        </div>
        <div className={styles.valueDisplay}>
          <strong>Current Value:</strong> {backgroundValue}
        </div>
        <div style={{ marginTop: '1rem', padding: '1rem', background: '#f8fafc', borderRadius: '6px', fontSize: '13px' }}>
          <strong>Test Steps:</strong>
          <ol style={{ margin: '0.5rem 0 0 1.5rem', padding: 0 }}>
            <li>Click the color swatch above to open the color picker</li>
            <li>Make sure you're in <strong>Gradient</strong> mode (use the button if needed)</li>
            <li>Try to <strong>drag</strong> the color selector chip in Color 1 or Color 2 pickers</li>
            <li>Try to <strong>drag</strong> the gradient direction slider</li>
            <li>If dragging works here but not in theme editor, the Dialog is blocking events</li>
          </ol>
        </div>
      </div>

      <div className={styles.instructions}>
        <h3>Testing Instructions:</h3>
        <ol>
          <li>Click the color swatch to open the color picker</li>
          <li>Try to <strong>drag</strong> the color selector chip in the gradient color picker</li>
          <li>Try to <strong>drag</strong> the gradient direction slider</li>
          <li>Check if dragging works here (outside Dialog)</li>
        </ol>
        <p>
          <strong>If dragging works here:</strong> The problem is the Dialog blocking events<br />
          <strong>If dragging doesn't work here:</strong> The problem is in the components themselves
        </p>
      </div>
      </div>
    </div>
  );
}

