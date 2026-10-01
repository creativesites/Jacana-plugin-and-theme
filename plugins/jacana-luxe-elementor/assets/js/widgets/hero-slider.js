/* hero-slider.js */
document.addEventListener('DOMContentLoaded', function() {
  const wrappers = document.querySelectorAll('.jacana-luxe-hero-wrapper');
  
  wrappers.forEach(wrapper => {
    const listItems = wrapper.querySelectorAll('.luxe-mountain-list li');
    const container = wrapper.querySelector('.luxe-container');
    const headingH1 = wrapper.querySelector('.luxe-headings h1');
    
    listItems.forEach(item => {
      item.addEventListener('click', () => {
        const img = item.querySelector('img');
        if (img) {
          const src = img.getAttribute('src');
          // Update container background
          container.style.setProperty('--luxe-bg-1', `url('${src}')`);
          // Update H1 clipping background
          headingH1.style.backgroundImage = `url('${src}')`;
        }
      });
    });
  });
});
