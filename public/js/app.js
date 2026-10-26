document.addEventListener('DOMContentLoaded', () => {
  const sidebar = document.getElementById('adminSidebar');
  const collapse = document.getElementById('sidebarCollapse');
  const mobileMenu = document.getElementById('mobileMenu');

  if (sidebar && collapse) {
    const saved = localStorage.getItem('cp-admin-sidebar');
    if (saved === 'collapsed' && window.matchMedia('(min-width: 801px)').matches) sidebar.classList.add('collapsed');
    collapse.addEventListener('click', () => {
      if (window.matchMedia('(max-width: 800px)').matches) {
        sidebar.classList.remove('mobile-open');
        return;
      }
      sidebar.classList.toggle('collapsed');
      localStorage.setItem('cp-admin-sidebar', sidebar.classList.contains('collapsed') ? 'collapsed' : 'expanded');
    });
  }
  if (sidebar && mobileMenu) {
    mobileMenu.addEventListener('click', () => sidebar.classList.toggle('mobile-open'));
    document.addEventListener('click', (event) => {
      if (window.matchMedia('(max-width: 800px)').matches && sidebar.classList.contains('mobile-open') && !sidebar.contains(event.target) && !mobileMenu.contains(event.target)) sidebar.classList.remove('mobile-open');
    });
  }

  const categorySearch = document.getElementById('categorySearch');
  const categoryId = document.getElementById('categoryId');
  const categoryOptions = document.getElementById('category-options');
  if (categorySearch && categoryId && categoryOptions) {
    const options = Array.from(categoryOptions.options);
    const syncCategory = () => {
      const match = options.find((option) => option.value.toLowerCase() === categorySearch.value.trim().toLowerCase());
      categoryId.value = match ? match.dataset.id : '';
    };
    categorySearch.addEventListener('input', syncCategory);
    categorySearch.addEventListener('change', syncCategory);
  }

  document.querySelectorAll('input[type="file"][data-max-files]').forEach((input) => {
    input.addEventListener('change', () => {
      const max = Number(input.dataset.maxFiles || 0);
      if (max > 0 && input.files.length > max) {
        alert('You can select a maximum of ' + max + ' files.');
        input.value = '';
      }
    });
  });

  document.querySelectorAll('input[name="product_images[]"]').forEach((input) => {
    input.addEventListener('change', () => {
      if (input.files.length > 12) {
        alert('You can upload up to 12 product images.');
        input.value = '';
        return;
      }
      const label = input.closest('.upload-zone');
      if (!label) return;
      let preview = label.querySelector('.upload-preview');
      if (preview) preview.textContent = input.files.length ? input.files.length + ' image(s) selected' : '';
    });
  });
});

  const aiButton = document.getElementById('aiGenerateCopy');
  if (aiButton) {
    aiButton.addEventListener('click', async () => {
      const name = document.querySelector('input[name="name"]')?.value.trim() || '';
      const notes = document.getElementById('aiNotes')?.value.trim() || document.querySelector('textarea[name="specification"]')?.value.trim() || '';
      const description = document.getElementById('productDescription');
      const status = document.getElementById('aiGenerateStatus');
      const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
      if (!name || !notes) {
        if (status) status.textContent = 'Enter the product name and some factual notes first.';
        return;
      }
      aiButton.disabled = true;
      aiButton.textContent = 'Generating with Gemini…';
      if (status) status.textContent = 'Generating a draft. Please review all claims before saving.';
      let locale = (document.documentElement.lang || 'en').toLowerCase();
      if (locale === 'zh-cn') locale = 'zh_CN';
      try {
        const response = await fetch('/admin/ai/product-copy', {
          method: 'POST',
          headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token},
          body: JSON.stringify({name, notes, locale})
        });
        const payload = await response.json();
        if (!response.ok) throw new Error(payload.message || 'AI request failed.');
        if (description) description.value = payload.description || '';
        if (status) status.textContent = 'Draft generated. Review and edit it before saving.';
      } catch (error) {
        if (status) status.textContent = error.message || 'Could not generate the draft. Check the AI settings.';
      } finally {
        aiButton.disabled = false;
        aiButton.textContent = '✦ Generate product description with Gemini';
      }
    });
  }