<?php

use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\CourseController as AdminCourseController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\HomeController as AdminHomeController;
use App\Http\Controllers\Admin\ImportExportController;
use App\Http\Controllers\Admin\MediaController as AdminMediaController;
use App\Http\Controllers\Admin\MenuController as AdminMenuController;
use App\Http\Controllers\Admin\MessageController as AdminMessageController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\PageBuilderController;
use App\Http\Controllers\Admin\PageController as AdminPageController;
use App\Http\Controllers\Admin\PluginController as AdminPluginController;
use App\Http\Controllers\Admin\PostController as AdminPostController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\SearchController as AdminSearchController;
use App\Http\Controllers\Admin\SettingController as AdminSettingController;
use App\Http\Controllers\Admin\TaxonomyController as AdminTaxonomyController;
use App\Http\Controllers\Admin\ThemeController as AdminThemeController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Panel\DashboardController as PanelDashboardController;
use App\Http\Controllers\Site\BlogController;
use App\Http\Controllers\Site\ContactController;
use App\Http\Controllers\Site\CourseController;
use App\Http\Controllers\Site\HomeController;
use App\Http\Controllers\Site\PageController;
use App\Http\Controllers\Site\SearchController;
use App\Http\Controllers\Site\ShopController;
use App\Http\Controllers\Site\SitemapController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| SEO, Sitemaps & Feeds
|--------------------------------------------------------------------------
*/
Route::controller(SitemapController::class)->group(function () {
    Route::get('/sitemap_index.xml', 'index')->name('sitemap.index');
    Route::get('/sitemap.xml', 'legacy')->name('sitemap');
    Route::get('/post-sitemap.xml', 'posts')->name('sitemap.posts');
    Route::get('/page-sitemap.xml', 'pages')->name('sitemap.pages');
    Route::get('/course-sitemap.xml', 'courses')->name('sitemap.courses');
    Route::get('/product-sitemap.xml', 'products')->name('sitemap.products');
    Route::get('/robots.txt', 'robots')->name('robots');
});

/*
|--------------------------------------------------------------------------
| Public Frontend (Site)
|--------------------------------------------------------------------------
*/

// Home & Search
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/search', [SearchController::class, 'index'])->name('search');

// Courses & LMS
Route::controller(CourseController::class)->prefix('courses')->name('courses.')->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/{slug}', 'show')->name('show');
    Route::get('/{slug}/preview/{lessonSlug}', 'previewLesson')->name('preview');
    Route::get('/{slug}/lessons/{lessonSlug}/file', 'serveLessonFile')->name('lesson.file');

    // Authenticated student actions
    Route::middleware('auth')->group(function () {
        Route::get('/{slug}/enroll-free', 'enrollFree')->name('enroll-free');
        Route::get('/{slug}/learn/{lessonSlug?}', 'learn')->name('learn');
        Route::post('/{slug}/lessons/{lessonSlug}/complete', 'completeLesson')->name('lesson.complete');
        Route::get('/{slug}/lessons/{lessonSlug}/download', 'downloadLesson')->name('lesson.download');
    });
});

// Legacy Product URLs (301 Redirect to Courses)
Route::redirect('/products', '/courses', 301)->name('products.index');
Route::get('/products/{slug}', fn () => redirect()->route('courses.index', [], 301))->name('products.show');

// Blog
Route::controller(BlogController::class)->prefix('blog')->name('blog.')->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/{slug}', 'show')->name('show');
});

// Pages & Institutional
Route::redirect('/why-bisan', '/about', 301)->name('why-bisan');
Route::redirect('/services', '/about', 301)->name('services');
Route::get('/about', [PageController::class, 'about'])->name('about');
Route::get('/contact', [ContactController::class, 'index'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])->name('contact.store');
Route::get('/p/{slug}', [PageController::class, 'show'])->name('pages.show');

/*
|--------------------------------------------------------------------------
| Shopping Cart & Checkout
|--------------------------------------------------------------------------
*/
Route::controller(ShopController::class)->group(function () {
    Route::get('/cart', 'cart')->name('cart.index');
    Route::post('/cart/add/{product}', 'addToCart')->name('cart.add');
    Route::put('/cart/{item}', 'updateCart')->name('cart.update');
    Route::delete('/cart/{item}', 'removeFromCart')->name('cart.remove');

    Route::get('/checkout/callback', 'callback')->name('checkout.callback');

    Route::middleware('auth')->group(function () {
        Route::get('/checkout', 'checkout')->name('checkout.index');
        Route::post('/checkout', 'processCheckout')->name('checkout.process');
        Route::get('/checkout/success/{order}', 'success')->name('checkout.success');
    });
});

/*
|--------------------------------------------------------------------------
| User Authentication (OTP)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login/otp', [AuthController::class, 'sendOtp'])->name('login.otp');
    Route::get('/login/verify', [AuthController::class, 'showVerify'])->name('login.verify');
    Route::post('/login/verify', [AuthController::class, 'verifyOtp'])->name('login.verify.submit');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

/*
|--------------------------------------------------------------------------
| User Panel (Dashboard)
|--------------------------------------------------------------------------
*/
Route::prefix('panel')->name('panel.')->middleware('auth')->controller(PanelDashboardController::class)->group(function () {
    Route::get('/', 'index')->name('dashboard');
    Route::get('/courses', 'courses')->name('courses');
    Route::get('/orders', 'orders')->name('orders');
    Route::get('/profile', 'profile')->name('profile');
    Route::put('/profile', 'updateProfile')->name('profile.update');
});

/*
|--------------------------------------------------------------------------
| Admin CMS Panel
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->group(function () {

    // Admin Auth
    Route::controller(AdminAuthController::class)->group(function () {
        Route::get('login', 'showLogin')->name('login');
        Route::post('login', 'login');
        Route::post('logout', 'logout')->name('logout');
    });

    // Protected Admin Routes
    Route::middleware(['cms.admin', 'cms.permission'])->group(function () {

        // Dashboard & Global Search
        Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
        Route::get('search', [AdminSearchController::class, 'index'])->name('search');

        // Home Page Customizer
        Route::get('home', [AdminHomeController::class, 'edit'])->name('home.edit');
        Route::put('home', [AdminHomeController::class, 'update'])->name('home.update');

        // Pages & Visual Page Builder
        Route::controller(AdminPageController::class)->prefix('pages')->name('pages.')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/create', 'create')->name('create');
            Route::post('/', 'store')->name('store');
            Route::get('/{page}/edit', 'edit')->name('edit');
            Route::put('/{page}', 'update')->name('update');
            Route::delete('/{page}', 'destroy')->name('destroy');
        });

        Route::controller(PageBuilderController::class)->prefix('pages/{page}')->name('pages.')->group(function () {
            Route::get('/builder', 'edit')->name('builder');
            Route::post('/builder', 'save')->name('builder.save');
            Route::post('/builder/preview', 'preview')->name('builder.preview');
            Route::get('/revisions', 'revisions')->name('revisions');
            Route::post('/revisions/{revision}/restore', 'restore')->name('revisions.restore');
        });

        // Blog Posts & Taxonomies
        Route::resource('posts', AdminPostController::class)->except(['show']);
        Route::resource('categories', AdminCategoryController::class)->except(['create', 'edit', 'show']);

        Route::controller(AdminTaxonomyController::class)->group(function () {
            Route::get('taxonomies', 'index')->name('taxonomies.index');
            Route::post('taxonomies', 'storeTaxonomy')->name('taxonomies.store');
            Route::post('taxonomies/{taxonomy}/terms', 'storeTerm')->name('taxonomies.terms.store');
            Route::delete('taxonomy-terms/{term}', 'destroyTerm')->name('taxonomies.terms.destroy');
        });

        // Courses & Curriculum (Sections & Lessons)
        Route::resource('products', AdminProductController::class)->except(['show']);

        Route::controller(AdminCourseController::class)->prefix('courses')->name('courses.')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/create', 'create')->name('create');
            Route::post('/', 'store')->name('store');
            Route::get('/{course}/edit', 'edit')->name('edit');
            Route::put('/{course}', 'update')->name('update');
            Route::delete('/{course}', 'destroy')->name('destroy');

            // Sections
            Route::post('/{course}/sections', 'storeSection')->name('sections.store');
            Route::put('/{course}/sections/{section}', 'updateSection')->name('sections.update');
            Route::delete('/{course}/sections/{section}', 'destroySection')->name('sections.destroy');

            // Lessons
            Route::post('/{course}/sections/{section}/lessons', 'storeLesson')->name('lessons.store');
            Route::put('/{course}/lessons/{lesson}', 'updateLesson')->name('lessons.update');
            Route::delete('/{course}/lessons/{lesson}', 'destroyLesson')->name('lessons.destroy');
        });

        // Menus
        Route::controller(AdminMenuController::class)->prefix('menus')->name('menus.')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/create', 'create')->name('create');
            Route::post('/', 'store')->name('store');
            Route::get('/{menu}/edit', 'edit')->name('edit');
            Route::put('/{menu}', 'update')->name('update');
            Route::post('/{menu}/tree', 'saveTree')->name('tree');
            Route::delete('/{menu}', 'destroy')->name('destroy');
        });

        // Themes & Plugins
        Route::controller(AdminThemeController::class)->prefix('themes')->name('themes.')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('/', 'store')->name('store');
            Route::post('/{slug}/activate', 'activate')->name('activate');
            Route::get('/{slug}/preview', 'preview')->name('preview');
        });

        Route::controller(AdminPluginController::class)->prefix('plugins')->name('plugins.')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('/', 'store')->name('store');
            Route::post('/{plugin}/toggle', 'toggle')->name('toggle');
        });

        // Media Library
        Route::controller(AdminMediaController::class)->prefix('media')->name('media.')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('/', 'store')->name('store');
            Route::delete('/{medium}', 'destroy')->name('destroy');
        });

        // Orders & Users
        Route::controller(AdminOrderController::class)->prefix('orders')->name('orders.')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/{order}', 'show')->name('show');
        });

        Route::resource('users', AdminUserController::class)->except(['show']);

        // Settings, Messages, Import/Export
        Route::controller(AdminSettingController::class)->group(function () {
            Route::get('settings', 'index')->name('settings.index');
            Route::put('settings', 'update')->name('settings.update');
        });

        Route::controller(AdminMessageController::class)->prefix('messages')->name('messages.')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/{message}', 'show')->name('show');
            Route::delete('/{message}', 'destroy')->name('destroy');
        });

        Route::controller(ImportExportController::class)->group(function () {
            Route::get('export', 'export')->name('export');
            Route::post('import', 'import')->name('import');
        });
    });
});
