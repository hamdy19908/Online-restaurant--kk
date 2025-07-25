<?php
// إعدادات رفع الصور
define('IMG_BB_API_KEY', '4bd31d5d415bf8f5cf9239d885c97465');
$allowed_types = ['image/jpeg', 'image/png', 'image/gif'];

// تحميل المنتجات من ملف JSON
$products_file = 'products.json';
$products = file_exists($products_file) ? json_decode(file_get_contents($products_file), true) : [];

// معالجة عمليات الإضافة/التعديل/الحذف
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                addProduct();
                break;
            case 'edit':
                editProduct();
                break;
            case 'delete':
                deleteProduct();
                break;
        }
    }
}

function addProduct() {
    global $products, $products_file;
    
    $image_url = handleImageUpload();
    if ($image_url === false) return;
    
    $newProduct = [
        'id' => getNextId(),
        'name' => htmlspecialchars($_POST['name']),
        'description' => htmlspecialchars($_POST['description']),
        'price' => (float)$_POST['price'],
        'category' => $_POST['category'],
        'image' => $image_url
    ];
    
    $products[] = $newProduct;
    saveProducts();
    
    header('Location: dashbord.php');
    exit;
}

function editProduct() {
    global $products, $products_file;
    
    $id = (int)$_POST['id'];
    $image_url = !empty($_FILES['image']['name']) ? handleImageUpload() : $_POST['existing_image'];
    if ($image_url === false) return;
    
    foreach ($products as &$product) {
        if ($product['id'] === $id) {
            $product['name'] = htmlspecialchars($_POST['name']);
            $product['description'] = htmlspecialchars($_POST['description']);
            $product['price'] = (float)$_POST['price'];
            $product['category'] = $_POST['category'];
            $product['image'] = $image_url;
            break;
        }
    }
    
    saveProducts();
    header('Location: index.php?success=edit');
    exit;
}

function deleteProduct() {
    global $products, $products_file;
    
    $id = (int)$_POST['id'];
    $products = array_filter($products, function($product) use ($id) {
        return $product['id'] !== $id;
    });
    
    saveProducts();
    header('Location: dashbord.php');
    exit;
}

function handleImageUpload() {
    global $allowed_types;
    
    if (!isset($_FILES['image']) || $_FILES['image']['error'] === UPLOAD_ERR_NO_FILE) {
        return isset($_POST['existing_image']) ? $_POST['existing_image'] : '';
    }
    
    $file = $_FILES['image'];
    
    // التحقق من وجود أخطاء
    if ($file['error'] !== UPLOAD_ERR_OK) {
        header('Location: index.php?error=upload');
        exit;
    }
    
    // التحقق من نوع الملف
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime_type, $allowed_types)) {
        header('Location: index.php?error=type');
        exit;
    }
    
    // رفع الصورة إلى ImgBB
    $image_url = uploadToImgBB($file['tmp_name']);
    if ($image_url === false) {
        header('Location: index.php?error=upload');
        exit;
    }
    
    return $image_url;
}

function uploadToImgBB($image_path) {
    $api_key = IMG_BB_API_KEY;
    $image = file_get_contents($image_path);
    $base64_image = base64_encode($image);
    
    $post_fields = [
        'key' => $api_key,
        'image' => $base64_image
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.imgbb.com/1/upload');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $result = json_decode($response, true);
    
    if ($result && isset($result['data']['url'])) {
        return $result['data']['url'];
    }
    
    return false;
}

function getNextId() {
    global $products;
    return count($products) > 0 ? max(array_column($products, 'id')) + 1 : 1;
}

function saveProducts() {
    global $products, $products_file;
    file_put_contents($products_file, json_encode(array_values($products), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}

function getCategoryName($category) {
    $categories = [
        'main' => 'أطباق رئيسية',
        'salads' => 'سلطات',
        'appetizers' => 'مقبلات',
        'desserts' => 'حلويات',
        'drinks' => 'مشروبات'
    ];
    return $categories[$category] ?? $category;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نظام إدارة المنتجات</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="dashboard">
        <!-- الشريط الجانبي -->
        <aside class="sidebar">
            <div class="logo">
                <i class="fas fa-utensils"></i>
                <h1>إدارة المنتجات</h1>
            </div>
            
            <nav class="menu">
                <a href="#" class="active"><i class="fas fa-box-open"></i> جميع المنتجات</a>
                <a href="#"><i class="fas fa-chart-bar"></i> الإحصائيات</a>
                <a href="#"><i class="fas fa-cog"></i> الإعدادات</a>
            </nav>
            
            <div class="user-panel">
                <div class="user-info">
                    <img src="https://ui-avatars.com/api/?name=مدير+النظام&background=4CAF50&color=fff" alt="User">
                    <div>
                        <h4>مدير النظام</h4>
                        <span>Admin</span>
                    </div>
                </div>
                <a href="#" class="logout"><i class="fas fa-sign-out-alt"></i></a>
            </div>
        </aside>
        
        <!-- المحتوى الرئيسي -->
        <main class="content">
            <header class="content-header">
                <h2><i class="fas fa-box-open"></i> إدارة المنتجات</h2>
                
                <div class="actions">
                    <button id="addProductBtn" class="btn btn-primary">
                        <i class="fas fa-plus"></i> إضافة منتج
                    </button>
                </div>
            </header>
            
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    <?php
                    $messages = [
                        'add' => 'تمت إضافة المنتج بنجاح',
                        'edit' => 'تم تحديث المنتج بنجاح',
                        'delete' => 'تم حذف المنتج بنجاح'
                    ];
                    echo $messages[$_GET['success']] ?? 'تمت العملية بنجاح';
                    ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-error">
                    <?php
                    $errors = [
                        'upload' => 'حدث خطأ أثناء رفع الصورة',
                        'size' => 'حجم الصورة كبير جداً (الحد الأقصى 5MB)',
                        'type' => 'نوع الملف غير مسموح به (JPEG, PNG, GIF فقط)',
                        'move' => 'حدث خطأ أثناء حفظ الصورة'
                    ];
                    echo $errors[$_GET['error']] ?? 'حدث خطأ غير معروف';
                    ?>
                </div>
            <?php endif; ?>
            
            <div class="filters">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" placeholder="ابحث عن منتج...">
                </div>
                
                <select id="categoryFilter">
                    <option value="all">جميع الفئات</option>
                    <option value="main">أطباق رئيسية</option>
                    <option value="salads">سلطات</option>
                    <option value="appetizers">مقبلات</option>
                    <option value="desserts">حلويات</option>
                    <option value="drinks">مشروبات</option>
                </select>
                
                <div class="sort-options">
                    <span>ترتيب حسب:</span>
                    <select id="sortBy">
                        <option value="name">الاسم</option>
                        <option value="price">السعر</option>
                        <option value="category">الفئة</option>
                    </select>
                </div>
            </div>
            
            <div class="products-grid" id="productsContainer">
                <?php foreach ($products as $product): ?>
                    <div class="product-card" data-id="<?= $product['id'] ?>" data-category="<?= $product['category'] ?>">
                        <div class="product-badge">
                            <?= getCategoryName($product['category']) ?>
                        </div>
                        
                        <div class="product-image">
                            <img src="<?= $product['image'] ?>" alt="<?= $product['name'] ?>">
                            <div class="product-actions">
                                <button class="btn-edit" data-id="<?= $product['id'] ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn-delete" data-id="<?= $product['id'] ?>">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="product-info">
                            <h3><?= $product['name'] ?></h3>
                            <p class="description"><?= $product['description'] ?></p>
                            
                            <div class="product-footer">
                                <span class="price"><?= number_format($product['price'], 2) ?> جنيه</span>
                                <div class="rating">
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star-half-alt"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <?php if (empty($products)): ?>
                    <div class="empty-state">
                        <img src="https://cdn.dribbble.com/users/888330/screenshots/2653750/media/b7459526f450fca8dae2f4389d5aa756.jpg" alt="No products">
                        <h3>لا توجد منتجات متاحة</h3>
                        <p>انقر على زر "إضافة منتج" لبدء إضافة منتجات جديدة</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <!-- نافذة إضافة/تعديل المنتج -->
    <div class="modal" id="productModal">
        <div class="modal-content">
            <span class="close-btn">&times;</span>
            <h2 id="modalTitle">إضافة منتج جديد</h2>
            
            <form id="productForm" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="productId">
                <input type="hidden" name="existing_image" id="existingImage">
                
                <div class="form-group">
                    <label for="productName">اسم المنتج</label>
                    <input type="text" id="productName" name="name" required>
                </div>
                
                <div class="form-group">
                    <label for="productDescription">الوصف</label>
                    <textarea id="productDescription" name="description" rows="3" required></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="productPrice">السعر ()</label>
                        <input type="number" id="productPrice" name="price" min="0" step="0.01" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="productCategory">الفئة</label>
                        <select id="productCategory" name="category" required>
                            <option value="main">أطباق رئيسية</option>
                            <option value="salads">سلطات</option>
                            <option value="appetizers">مقبلات</option>
                            <option value="desserts">حلويات</option>
                            <option value="drinks">مشروبات</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="productImage">صورة المنتج</label>
                    <input type="file" id="productImage" name="image" accept="image/*">
                    <div class="image-preview" id="imagePreview">
                        <img src="" alt="معاينة الصورة" id="previewImage">
                        <div class="no-preview">لا توجد صورة</div>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" id="cancelBtn">إلغاء</button>
                    <button type="submit" class="btn btn-primary">حفظ المنتج</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- نافذة تأكيد الحذف -->
    <div class="modal" id="confirmModal">
        <div class="modal-content small">
            <h2>تأكيد الحذف</h2>
            <p>هل أنت متأكد أنك تريد حذف هذا المنتج؟ لا يمكن التراجع عن هذه العملية.</p>
            
            <form id="deleteForm" method="POST">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" id="deleteId">
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" id="cancelDeleteBtn">إلغاء</button>
                    <button type="submit" class="btn btn-danger">حذف المنتج</button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="js/script.js"></script>
</body>
</html>