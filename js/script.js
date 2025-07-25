document.addEventListener('DOMContentLoaded', function() {
    // عناصر DOM
    const addProductBtn = document.getElementById('addProductBtn');
    const productModal = document.getElementById('productModal');
    const confirmModal = document.getElementById('confirmModal');
    const closeBtns = document.querySelectorAll('.close-btn, #cancelBtn');
    const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');
    const productForm = document.getElementById('productForm');
    const deleteForm = document.getElementById('deleteForm');
    const searchInput = document.getElementById('searchInput');
    const categoryFilter = document.getElementById('categoryFilter');
    const sortBy = document.getElementById('sortBy');
    const productImage = document.getElementById('productImage');
    const imagePreview = document.getElementById('imagePreview');
    const previewImage = document.getElementById('previewImage');
    const noPreview = imagePreview.querySelector('.no-preview');
    const existingImage = document.getElementById('existingImage');
    
    // متغيرات
    let products = Array.from(document.querySelectorAll('.product-card'));
    
    // الأحداث
    addProductBtn.addEventListener('click', openAddModal);
    closeBtns.forEach(btn => btn.addEventListener('click', closeModal));
    cancelDeleteBtn.addEventListener('click', closeModal);
    
    // أحداث التعديل والحذف
    document.querySelectorAll('.btn-edit').forEach(btn => {
        btn.addEventListener('click', openEditModal);
    });
    
    document.querySelectorAll('.btn-delete').forEach(btn => {
        btn.addEventListener('click', openDeleteModal);
    });
    
    // البحث والتصفية
    searchInput.addEventListener('input', filterProducts);
    categoryFilter.addEventListener('change', filterProducts);
    sortBy.addEventListener('change', sortProducts);
    
    // معاينة الصورة
    productImage.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(event) {
                previewImage.src = event.target.result;
                noPreview.style.display = 'none';
                previewImage.style.display = 'block';
            };
            reader.readAsDataURL(file);
        } else {
            noPreview.style.display = 'block';
            previewImage.style.display = 'none';
        }
    });
    
    // فتح نافذة الإضافة
    function openAddModal() {
        document.getElementById('modalTitle').textContent = 'إضافة منتج جديد';
        document.getElementById('formAction').value = 'add';
        productForm.reset();
        noPreview.style.display = 'block';
        previewImage.style.display = 'none';
        existingImage.value = '';
        productModal.style.display = 'flex';
    }
    
    // فتح نافذة التعديل
    function openEditModal(e) {
        const productId = e.currentTarget.getAttribute('data-id');
        const productCard = document.querySelector(`.product-card[data-id="${productId}"]`);
        
        if (productCard) {
            document.getElementById('modalTitle').textContent = 'تعديل المنتج';
            document.getElementById('formAction').value = 'edit';
            document.getElementById('productId').value = productId;
            document.getElementById('productName').value = productCard.querySelector('h3').textContent;
            document.getElementById('productDescription').value = productCard.querySelector('.description').textContent;
            document.getElementById('productPrice').value = productCard.querySelector('.price').textContent.replace(' جنيه', '').trim();
            document.getElementById('productCategory').value = productCard.getAttribute('data-category');
            
            const imageUrl = productCard.querySelector('.product-image img').getAttribute('src');
            existingImage.value = imageUrl;
            
            // عرض الصورة الحالية
            if (imageUrl) {
                previewImage.src = imageUrl;
                noPreview.style.display = 'none';
                previewImage.style.display = 'block';
            } else {
                noPreview.style.display = 'block';
                previewImage.style.display = 'none';
            }
            
            // إعادة تعيين حقل رفع الصورة
            productImage.value = '';
            
            productModal.style.display = 'flex';
        }
    }
    
    // فتح نافذة الحذف
    function openDeleteModal(e) {
        const productId = e.currentTarget.getAttribute('data-id');
        document.getElementById('deleteId').value = productId;
        confirmModal.style.display = 'flex';
    }
    
    // إغلاق النوافذ المنبثقة
    function closeModal() {
        productModal.style.display = 'none';
        confirmModal.style.display = 'none';
    }
    
    // تصفية المنتجات
    function filterProducts() {
        const searchTerm = searchInput.value.toLowerCase();
        const category = categoryFilter.value;
        
        products.forEach(product => {
            const productName = product.querySelector('h3').textContent.toLowerCase();
            const productDesc = product.querySelector('.description').textContent.toLowerCase();
            const productCategory = product.getAttribute('data-category');
            
            const matchesSearch = productName.includes(searchTerm) || productDesc.includes(searchTerm);
            const matchesCategory = category === 'all' || productCategory === category;
            
            if (matchesSearch && matchesCategory) {
                product.style.display = 'block';
            } else {
                product.style.display = 'none';
            }
        });
    }
    
    // ترتيب المنتجات
    function sortProducts() {
        const sortValue = sortBy.value;
        const container = document.getElementById('productsContainer');
        const productCards = Array.from(container.querySelectorAll('.product-card'));
        
        productCards.sort((a, b) => {
            if (sortValue === 'name') {
                return a.querySelector('h3').textContent.localeCompare(b.querySelector('h3').textContent);
            } else if (sortValue === 'price') {
                const priceA = parseFloat(a.querySelector('.price').textContent.replace(' جنيه', ''));
                const priceB = parseFloat(b.querySelector('.price').textContent.replace(' جنيه', ''));
                return priceA - priceB;
            } else if (sortValue === 'category') {
                return a.getAttribute('data-category').localeCompare(b.getAttribute('data-category'));
            }
            return 0;
        });
        
        // إعادة ترتيب المنتجات في الـ DOM
        productCards.forEach(card => container.appendChild(card));
    }
    
    // إغلاق النوافذ عند النقر خارجها
    window.addEventListener('click', function(e) {
        if (e.target === productModal) {
            closeModal();
        }
        if (e.target === confirmModal) {
            closeModal();
        }
    });
});