<?php

use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\CourseController as AdminCourseController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\HomeController as AdminHomeController;
use App\Http\Controllers\Admin\MediaController as AdminMediaController;
use App\Http\Controllers\Admin\MessageController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\PageController as AdminPageController;
use App\Http\Controllers\Admin\PostController as AdminPostController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Panel\DashboardController as PanelDashboardController;
use App\Http\Controllers\Site\BlogController;
use App\Http\Controllers\Site\ContactController;
use App\Http\Controllers\Site\CourseController;
use App\Http\Controllers\Site\HomeController;
use App\Http\Controllers\Site\PageController;
use App\Http\Controllers\Site\ProductController;
use App\Http\Controllers\Site\ShopController;
use App\Http\Controllers\Site\SitemapController;
use Illuminate\Support\Facades\Route;

Route::get('/sitemap_index.xml', [SitemapController::class, 'index'])->name('sitemap.index');
Route::get('/sitemap.xml', [SitemapController::class, 'legacy'])->name('sitemap');
Route::get('/post-sitemap.xml', [SitemapController::class, 'posts'])->name('sitemap.posts');
Route::get('/page-sitemap.xml', [SitemapController::class, 'pages'])->name('sitemap.pages');
Route::get('/course-sitemap.xml', [SitemapController::class, 'courses'])->name('sitemap.courses');
Route::get('/product-sitemap.xml', [SitemapController::class, 'products'])->name('sitemap.products');
Route::get('/robots.txt', [SitemapController::class, 'robots'])->name('robots');

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/products/{slug}', [ProductController::class, 'show'])->name('products.show');
Route::get('/courses', [CourseController::class, 'index'])->name('courses.index');
Route::get('/courses/{slug}', [CourseController::class, 'show'])->name('courses.show');
Route::get('/courses/{slug}/enroll-free', [CourseController::class, 'enrollFree'])->name('courses.enroll-free')->middleware('auth');
Route::get('/courses/{slug}/learn/{lessonSlug?}', [CourseController::class, 'learn'])->name('courses.learn')->middleware('auth');
Route::post('/courses/{slug}/lessons/{lessonSlug}/complete', [CourseController::class, 'completeLesson'])->name('courses.lesson.complete')->middleware('auth');
Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/{slug}', [BlogController::class, 'show'])->name('blog.show');
Route::get('/services', [PageController::class, 'services'])->name('services');
Route::get('/why-bisan', [PageController::class, 'whyBisan'])->name('why-bisan');
Route::get('/about', [PageController::class, 'about'])->name('about');
Route::get('/contact', [ContactController::class, 'index'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])->name('contact.store');
Route::get('/p/{slug}', [PageController::class, 'show'])->name('pages.show');

Route::get('/cart', [ShopController::class, 'cart'])->name('cart.index');
Route::post('/cart/add/{product}', [ShopController::class, 'addToCart'])->name('cart.add');
Route::put('/cart/{item}', [ShopController::class, 'updateCart'])->name('cart.update');
Route::delete('/cart/{item}', [ShopController::class, 'removeFromCart'])->name('cart.remove');
Route::get('/checkout', [ShopController::class, 'checkout'])->name('checkout.index')->middleware('auth');
Route::post('/checkout', [ShopController::class, 'processCheckout'])->name('checkout.process')->middleware('auth');
Route::get('/checkout/callback', [ShopController::class, 'callback'])->name('checkout.callback');
Route::get('/checkout/success/{order}', [ShopController::class, 'success'])->name('checkout.success')->middleware('auth');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login/otp', [AuthController::class, 'sendOtp'])->name('login.otp');
    Route::get('/login/verify', [AuthController::class, 'showVerify'])->name('login.verify');
    Route::post('/login/verify', [AuthController::class, 'verifyOtp'])->name('login.verify.submit');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

Route::prefix('panel')->name('panel.')->middleware('auth')->group(function () {
    Route::get('/', [PanelDashboardController::class, 'index'])->name('dashboard');
    Route::get('/courses', [PanelDashboardController::class, 'courses'])->name('courses');
    Route::get('/orders', [PanelDashboardController::class, 'orders'])->name('orders');
    Route::get('/profile', [PanelDashboardController::class, 'profile'])->name('profile');
    Route::put('/profile', [PanelDashboardController::class, 'updateProfile'])->name('profile.update');
});

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('login', [AdminAuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AdminAuthController::class, 'login']);
    Route::post('logout', [AdminAuthController::class, 'logout'])->name('logout');

    Route::middleware('cms.admin')->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('home', [AdminHomeController::class, 'edit'])->name('home.edit');
        Route::put('home', [AdminHomeController::class, 'update'])->name('home.update');
        Route::get('pages', [AdminPageController::class, 'index'])->name('pages.index');
        Route::get('pages/create', [AdminPageController::class, 'create'])->name('pages.create');
        Route::post('pages', [AdminPageController::class, 'store'])->name('pages.store');
        Route::get('pages/{page}/edit', [AdminPageController::class, 'edit'])->name('pages.edit');
        Route::put('pages/{page}', [AdminPageController::class, 'update'])->name('pages.update');
        Route::delete('pages/{page}', [AdminPageController::class, 'destroy'])->name('pages.destroy');
        Route::resource('posts', AdminPostController::class)->except(['show']);
        Route::get('categories', [AdminCategoryController::class, 'index'])->name('categories.index');
        Route::post('categories', [AdminCategoryController::class, 'store'])->name('categories.store');
        Route::put('categories/{category}', [AdminCategoryController::class, 'update'])->name('categories.update');
        Route::delete('categories/{category}', [AdminCategoryController::class, 'destroy'])->name('categories.destroy');
        Route::get('media', [AdminMediaController::class, 'index'])->name('media.index');
        Route::post('media', [AdminMediaController::class, 'store'])->name('media.store');
        Route::delete('media/{medium}', [AdminMediaController::class, 'destroy'])->name('media.destroy');
        Route::resource('products', AdminProductController::class)->except(['show']);
        Route::get('courses', [AdminCourseController::class, 'index'])->name('courses.index');
        Route::get('courses/create', [AdminCourseController::class, 'create'])->name('courses.create');
        Route::post('courses', [AdminCourseController::class, 'store'])->name('courses.store');
        Route::get('courses/{course}/edit', [AdminCourseController::class, 'edit'])->name('courses.edit');
        Route::put('courses/{course}', [AdminCourseController::class, 'update'])->name('courses.update');
        Route::delete('courses/{course}', [AdminCourseController::class, 'destroy'])->name('courses.destroy');
        Route::post('courses/{course}/sections', [AdminCourseController::class, 'storeSection'])->name('courses.sections.store');
        Route::delete('courses/{course}/sections/{section}', [AdminCourseController::class, 'destroySection'])->name('courses.sections.destroy');
        Route::post('courses/{course}/sections/{section}/lessons', [AdminCourseController::class, 'storeLesson'])->name('courses.lessons.store');
        Route::delete('courses/{course}/lessons/{lesson}', [AdminCourseController::class, 'destroyLesson'])->name('courses.lessons.destroy');
        Route::get('orders', [AdminOrderController::class, 'index'])->name('orders.index');
        Route::get('orders/{order}', [AdminOrderController::class, 'show'])->name('orders.show');
        Route::resource('users', AdminUserController::class)->except(['show']);
        Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
        Route::put('settings', [SettingController::class, 'update'])->name('settings.update');
        Route::get('messages', [MessageController::class, 'index'])->name('messages.index');
        Route::get('messages/{message}', [MessageController::class, 'show'])->name('messages.show');
        Route::delete('messages/{message}', [MessageController::class, 'destroy'])->name('messages.destroy');
    });
});
