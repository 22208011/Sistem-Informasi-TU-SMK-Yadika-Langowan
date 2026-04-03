// Flux Disclosure Diagnostic Script
// Copy and paste this into your browser console to diagnose the sidebar issue

console.log('=== Flux Disclosure Diagnostic ===\n');

// 1. Check if custom elements are defined
console.log('1. Custom Elements:');
console.log('   - ui-disclosure defined:', customElements.get('ui-disclosure') !== undefined);
console.log('   - ui-sidebar defined:', customElements.get('ui-sidebar') !== undefined);
console.log('   - ui-dropdown defined:', customElements.get('ui-dropdown') !== undefined);

// 2. Check Alpine
console.log('\n2. Alpine.js:');
console.log('   - window.Alpine exists:', typeof window.Alpine !== 'undefined');
if (window.Alpine) {
    console.log('   - Alpine version:', window.Alpine.version);
    console.log('   - Alpine started:', window.Alpine.reactive !== undefined);
}

// 3. Check Livewire
console.log('\n3. Livewire:');
console.log('   - window.Livewire exists:', typeof window.Livewire !== 'undefined');

// 4. Find ui-disclosure elements
console.log('\n4. UI Disclosure Elements:');
const disclosures = document.querySelectorAll('ui-disclosure');
console.log('   - Total found:', disclosures.length);

disclosures.forEach((el, i) => {
    const button = el.querySelector('button, ui-button');
    const lastChild = el.lastElementChild;
    const heading = el.querySelector('.text-sm.font-medium')?.textContent?.trim() || 'No heading';
    
    console.log(`\n   [${i}] "${heading}":`);
    console.log(`       - Has button: ${button !== null}`);
    console.log(`       - Last child tag: ${lastChild?.tagName}`);
    console.log(`       - Last child is button: ${lastChild === button}`);
    console.log(`       - Has _controllable: ${el._controllable !== undefined}`);
    console.log(`       - data-open: ${el.hasAttribute('data-open')}`);
    
    // Check if panel is the last child (IMPORTANT!)
    if (lastChild === button) {
        console.error('       ⚠️ PROBLEM: Button is the last child, panel detection will fail!');
    }
    
    // Test click manually
    if (button && !el._tested) {
        el._tested = true;
        button.addEventListener('click', () => {
            console.log(`       Click on "${heading}" - data-open after:`, el.hasAttribute('data-open'));
        });
    }
});

// 5. Check for console errors
console.log('\n5. Check browser console for errors above this message');
console.log('   Look for: "ui-disclosure: no panel element found"');

// 6. Test a click programmatically
console.log('\n6. Testing first disclosure click...');
if (disclosures[0]) {
    const btn = disclosures[0].querySelector('button');
    if (btn) {
        console.log('   Clicking button...');
        const beforeOpen = disclosures[0].hasAttribute('data-open');
        btn.click();
        const afterOpen = disclosures[0].hasAttribute('data-open');
        console.log(`   Before click: data-open=${beforeOpen}`);
        console.log(`   After click: data-open=${afterOpen}`);
        console.log(`   Toggle working: ${beforeOpen !== afterOpen ? 'YES ✓' : 'NO ✗'}`);
    }
}

console.log('\n=== Diagnostic Complete ===');
