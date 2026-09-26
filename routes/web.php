<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\CronjobController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeveloperController;
use App\Http\Controllers\DomainController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InstallController;
use App\Http\Controllers\LinkController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PixelController;
use App\Http\Controllers\PricingController;
use App\Http\Controllers\QrController;
use App\Http\Controllers\RedirectController;
use App\Http\Controllers\SpaceController;
use App\Http\Controllers\StatController;
use App\Http\Controllers\UpdateController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Locale routes
Route::post('/locale', [LocaleController::class, 'updateLocale'])->name('locale');

// Install routes
Route::prefix('install')->group(function () {
    Route::middleware('install')->group(function () {
        Route::get('/', [InstallController::class, 'index'])->name('install');
        Route::get('/requirements', [InstallController::class, 'requirements'])->name('install.requirements');
        Route::get('/permissions', [InstallController::class, 'permissions'])->name('install.permissions');
        Route::get('/database', [InstallController::class, 'database'])->name('install.database');
        Route::get('/account', [InstallController::class, 'account'])->name('install.account');

        Route::post('/database', [InstallController::class, 'storeConfig']);
        Route::post('/account', [InstallController::class, 'storeDatabase']);
    });

    Route::get('/complete', [InstallController::class, 'complete'])->name('install.complete');
});

// Remote Redirect routes
if (config()->has('settings.title') && isset(parse_url(config('app.url'))['host']) && request()->getHost() != parse_url(config('app.url'))['host']) {
    Route::get('/{id}/+', [RedirectController::class, 'index'])->name('link.preview');
    Route::get('/{id}', [RedirectController::class, 'index'])->name('link.redirect');
    Route::post('/{id}/password', [RedirectController::class, 'validateRedirectPassword']);
    Route::post('/{id}/tracking-consent', [RedirectController::class, 'validateTrackingConsent']);
    Route::post('/{id}/sensitive-consent', [RedirectController::class, 'validateSensitiveConsent']);
}

// Auth routes
Auth::routes(['verify' => true]);
Route::get(ltrim(config('services.google.redirect'), '/'), [LoginController::class, 'google'])->name('login.google');
Route::get(ltrim(config('services.azure.redirect'), '/'), [LoginController::class, 'microsoft'])->name('login.microsoft');
Route::post(ltrim(config('services.apple.redirect'), '/'), [LoginController::class, 'apple'])->name('login.apple');
Route::post('login/tfa/validate', [LoginController::class, 'validateTfaCode'])->name('login.tfa.validate');
Route::post('login/tfa/resend', [LoginController::class, 'resendTfaCode'])->name('login.tfa.resend');

// Update routes
Route::prefix('update')->group(function () {
    Route::get('/', [UpdateController::class, 'index'])->name('update');
    Route::get('/overview', [UpdateController::class, 'overview'])->name('update.overview');
    Route::get('/complete', [UpdateController::class, 'complete'])->name('update.complete');

    Route::post('/overview', [UpdateController::class, 'updateDatabase']);
});

// Home routes
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::post('/shorten', [HomeController::class, 'storeLink'])->middleware('throttle:10,1')->name('guest');

// Contact routes
Route::get('/contact', [ContactController::class, 'index'])->name('contact');
Route::post('/contact', [ContactController::class, 'send'])->middleware('throttle:5,10');

// Page routes
Route::get('/pages/{id}', [PageController::class, 'show'])->name('pages.show');

// Dashboard routes
Route::get('/dashboard', [DashboardController::class, 'index'])->middleware('verified')->name('dashboard');

// Link routes
Route::get('/links', [LinkController::class, 'index'])->middleware('verified')->name('links');
Route::get('/links/{id}/edit', [LinkController::class, 'edit'])->middleware('verified')->name('links.edit');
Route::get('/links/export', [LinkController::class, 'export'])->middleware('verified')->name('links.export');
Route::post('/links/new', [LinkController::class, 'store'])->middleware('verified')->name('links.new');
Route::post('/links/{id}/edit', [LinkController::class, 'update'])->middleware('verified');
Route::post('/links/{id}/destroy', [LinkController::class, 'destroy'])->middleware('verified')->name('links.destroy');

// Space routes
Route::get('/spaces', [SpaceController::class, 'index'])->middleware('verified')->name('spaces');
Route::get('/spaces/new', [SpaceController::class, 'create'])->middleware('verified')->name('spaces.new');
Route::get('/spaces/{id}/edit', [SpaceController::class, 'edit'])->middleware('verified')->name('spaces.edit');
Route::post('/spaces/new', [SpaceController::class, 'store'])->middleware('verified');
Route::post('/spaces/{id}/edit', [SpaceController::class, 'update'])->middleware('verified');
Route::post('/spaces/{id}/destroy', [SpaceController::class, 'destroy'])->middleware('verified')->name('spaces.destroy');

// Domain routes
Route::get('/domains', [DomainController::class, 'index'])->middleware('verified')->name('domains');
Route::get('/domains/new', [DomainController::class, 'create'])->middleware('verified')->name('domains.new');
Route::get('/domains/{id}/edit', [DomainController::class, 'edit'])->middleware('verified')->name('domains.edit');
Route::post('/domains/new', [DomainController::class, 'store'])->middleware('verified');
Route::post('/domains/{id}/edit', [DomainController::class, 'update'])->middleware('verified');
Route::post('/domains/{id}/destroy', [DomainController::class, 'destroy'])->middleware('verified')->name('domains.destroy');

// Pixel routes
Route::get('/pixels', [PixelController::class, 'index'])->middleware('verified')->name('pixels');
Route::get('/pixels/new', [PixelController::class, 'create'])->middleware('verified')->name('pixels.new');
Route::get('/pixels/{id}/edit', [PixelController::class, 'edit'])->middleware('verified')->name('pixels.edit');
Route::post('/pixels/new', [PixelController::class, 'store'])->middleware('verified');
Route::post('/pixels/{id}/edit', [PixelController::class, 'update'])->middleware('verified');
Route::post('/pixels/{id}/destroy', [PixelController::class, 'destroy'])->middleware('verified')->name('pixels.destroy');

// Stat routes
Route::prefix('/stats/{id}')->group(function () {
    Route::get('/', [StatController::class, 'index'])->name('stats.overview');

    Route::get('/referrers', [StatController::class, 'referrers'])->name('stats.referrers');
    Route::get('/countries', [StatController::class, 'countries'])->name('stats.countries');
    Route::get('/cities', [StatController::class, 'cities'])->name('stats.cities');
    Route::get('/languages', [StatController::class, 'languages'])->name('stats.languages');
    Route::get('/browsers', [StatController::class, 'browsers'])->name('stats.browsers');
    Route::get('/operating-systems', [StatController::class, 'operatingSystems'])->name('stats.operating_systems');
    Route::get('/devices', [StatController::class, 'devices'])->name('stats.devices');

    Route::prefix('/export')->group(function () {
        Route::get('/referrers', [StatController::class, 'exportReferrers'])->name('stats.export.referrers');
        Route::get('/countries', [StatController::class, 'exportCountries'])->name('stats.export.countries');
        Route::get('/cities', [StatController::class, 'exportCities'])->name('stats.export.cities');
        Route::get('/languages', [StatController::class, 'exportLanguages'])->name('stats.export.languages');
        Route::get('/browsers', [StatController::class, 'exportBrowsers'])->name('stats.export.browsers');
        Route::get('/operating-systems', [StatController::class, 'exportOperatingSystems'])->name('stats.export.operating_systems');
        Route::get('/devices', [StatController::class, 'exportDevices'])->name('stats.export.devices');
    });

    Route::post('/password', [StatController::class, 'validatePassword'])->name('stats.password');
});

// QR routes
Route::get('/qr/{id}', [QrController::class, 'index'])->name('qr');

// Account routes
Route::prefix('account')->middleware('verified')->group(function () {
    Route::get('/', [AccountController::class, 'index'])->name('account');

    Route::get('/profile', [AccountController::class, 'profile'])->name('account.profile');
    Route::post('/profile', [AccountController::class, 'updateProfile'])->name('account.profile.update');
    Route::post('/profile/resend', [AccountController::class, 'resendAccountEmailConfirmation'])->name('account.profile.resend');
    Route::post('/profile/cancel', [AccountController::class, 'cancelAccountEmailConfirmation'])->name('account.profile.cancel');

    Route::get('/security', [AccountController::class, 'security'])->name('account.security');
    Route::post('/security', [AccountController::class, 'updateSecurity']);

    Route::get('/preferences', [AccountController::class, 'preferences'])->name('account.preferences');
    Route::post('/preferences', [AccountController::class, 'updatePreferences']);

    Route::get('/plan', [AccountController::class, 'plan'])->name('account.plan');
    Route::post('/plan', [AccountController::class, 'updatePlan'])->middleware('payment');

    Route::get('/payments', [AccountController::class, 'indexPayments'])->middleware('payment')->name('account.payments');
    Route::get('/payments/{id}/edit', [AccountController::class, 'editPayment'])->middleware('payment')->name('account.payments.edit');
    Route::post('/payments/{id}/cancel', [AccountController::class, 'cancelPayment'])->name('account.payments.cancel');

    Route::get('/invoices/{id}', [AccountController::class, 'showInvoice'])->middleware('payment')->name('account.invoices.show');

    Route::get('/api', [AccountController::class, 'api'])->name('account.api');
    Route::post('/api', [AccountController::class, 'updateApi']);

    Route::get('/delete', [AccountController::class, 'delete'])->name('account.delete');
    Route::post('/destroy', [AccountController::class, 'destroyUser'])->name('account.destroy');
});

// Admin routes
Route::prefix('admin')->middleware(['admin', 'license'])->group(function () {
    Route::redirect('/', 'admin/dashboard');

    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');

    Route::get('/settings/{id}', [AdminController::class, 'settings'])->name('admin.settings');
    Route::post('/settings/{id}', [AdminController::class, 'updateSetting']);

    Route::get('/users', [AdminController::class, 'indexUsers'])->name('admin.users');
    Route::get('/users/new', [AdminController::class, 'createUser'])->name('admin.users.new');
    Route::get('/users/{id}/edit', [AdminController::class, 'editUser'])->name('admin.users.edit');
    Route::post('/users/new', [AdminController::class, 'storeUser']);
    Route::post('/users/{id}/edit', [AdminController::class, 'updateUser']);
    Route::post('/users/{id}/destroy', [AdminController::class, 'destroyUser'])->name('admin.users.destroy');
    Route::post('/users/{id}/disable', [AdminController::class, 'disableUser'])->name('admin.users.disable');
    Route::post('/users/{id}/restore', [AdminController::class, 'restoreUser'])->name('admin.users.restore');
    Route::post('/users/{id}/login', [AdminController::class, 'loginUser'])->name('admin.users.login');

    Route::get('/pages', [AdminController::class, 'indexPages'])->name('admin.pages');
    Route::get('/pages/new', [AdminController::class, 'createPage'])->name('admin.pages.new');
    Route::get('/pages/{id}/edit', [AdminController::class, 'editPage'])->name('admin.pages.edit');
    Route::post('/pages/new', [AdminController::class, 'storePage']);
    Route::post('/pages/{id}/edit', [AdminController::class, 'updatePage']);
    Route::post('/pages/{id}/destroy', [AdminController::class, 'destroyPage'])->name('admin.pages.destroy');

    Route::get('/payments', [AdminController::class, 'indexPayments'])->name('admin.payments');
    Route::get('/payments/{id}/edit', [AdminController::class, 'editPayment'])->name('admin.payments.edit');
    Route::post('/payments/{id}/approve', [AdminController::class, 'approvePayment'])->name('admin.payments.approve');
    Route::post('/payments/{id}/cancel', [AdminController::class, 'cancelPayment'])->name('admin.payments.cancel');

    Route::get('/invoices/{id}', [AdminController::class, 'showInvoice'])->name('admin.invoices.show');

    Route::get('/plans', [AdminController::class, 'indexPlans'])->name('admin.plans');
    Route::get('/plans/new', [AdminController::class, 'createPlan'])->name('admin.plans.new');
    Route::get('/plans/{id}/edit', [AdminController::class, 'editPlan'])->name('admin.plans.edit');
    Route::post('/plans/new', [AdminController::class, 'storePlan']);
    Route::post('/plans/{id}/edit', [AdminController::class, 'updatePlan']);
    Route::post('/plans/{id}/disable', [AdminController::class, 'disablePlan'])->name('admin.plans.disable');
    Route::post('/plans/{id}/restore', [AdminController::class, 'restorePlan'])->name('admin.plans.restore');

    Route::get('/coupons', [AdminController::class, 'indexCoupons'])->name('admin.coupons');
    Route::get('/coupons/new', [AdminController::class, 'createCoupon'])->name('admin.coupons.new');
    Route::get('/coupons/{id}/edit', [AdminController::class, 'editCoupon'])->name('admin.coupons.edit');
    Route::post('/coupons/new', [AdminController::class, 'storeCoupon']);
    Route::post('/coupons/{id}/edit', [AdminController::class, 'updateCoupon']);
    Route::post('/coupons/{id}/disable', [AdminController::class, 'disableCoupon'])->name('admin.coupons.disable');
    Route::post('/coupons/{id}/restore', [AdminController::class, 'restoreCoupon'])->name('admin.coupons.restore');

    Route::get('/tax-rates', [AdminController::class, 'indexTaxRates'])->name('admin.tax_rates');
    Route::get('/tax-rates/new', [AdminController::class, 'createTaxRate'])->name('admin.tax_rates.new');
    Route::get('/tax-rates/{id}/edit', [AdminController::class, 'editTaxRate'])->name('admin.tax_rates.edit');
    Route::post('/tax-rates/new', [AdminController::class, 'storeTaxRate']);
    Route::post('/tax-rates/{id}/edit', [AdminController::class, 'updateTaxRate']);
    Route::post('/tax-rates/{id}/disable', [AdminController::class, 'disableTaxRate'])->name('admin.tax_rates.disable');
    Route::post('/tax-rates/{id}/restore', [AdminController::class, 'restoreTaxRate'])->name('admin.tax_rates.restore');

    Route::get('/links', [AdminController::class, 'indexLinks'])->name('admin.links');
    Route::get('/links/{id}/edit', [AdminController::class, 'editLink'])->name('admin.links.edit');
    Route::post('/links/{id}/edit', [AdminController::class, 'updateLink']);
    Route::post('/links/{id}/destroy', [AdminController::class, 'destroyLink'])->name('admin.links.destroy');

    Route::get('/spaces', [AdminController::class, 'indexSpaces'])->name('admin.spaces');
    Route::get('/spaces/{id}/edit', [AdminController::class, 'editSpace'])->name('admin.spaces.edit');
    Route::post('/spaces/{id}/edit', [AdminController::class, 'updateSpace']);
    Route::post('/spaces/{id}/destroy', [AdminController::class, 'destroySpace'])->name('admin.spaces.destroy');

    Route::get('/domains', [AdminController::class, 'indexDomains'])->name('admin.domains');
    Route::get('/domains/new', [AdminController::class, 'createDomain'])->name('admin.domains.new');
    Route::get('/domains/{id}/edit', [AdminController::class, 'editDomain'])->name('admin.domains.edit');
    Route::post('/domains/new', [AdminController::class, 'storeDomain']);
    Route::post('/domains/{id}/edit', [AdminController::class, 'updateDomain']);
    Route::post('/domains/{id}/destroy', [AdminController::class, 'destroyDomain'])->name('admin.domains.destroy');

    Route::get('/pixels', [AdminController::class, 'indexPixels'])->name('admin.pixels');
    Route::get('/pixels/{id}/edit', [AdminController::class, 'editPixel'])->name('admin.pixels.edit');
    Route::post('/pixels/{id}/edit', [AdminController::class, 'updatePixel']);
    Route::post('/pixels/{id}/destroy', [AdminController::class, 'destroyPixel'])->name('admin.pixels.destroy');
});

// Pricing routes
Route::prefix('pricing')->middleware('payment')->group(function () {
    Route::get('/', [PricingController::class, 'index'])->name('pricing');
});

// Checkout routes
Route::prefix('checkout')->middleware(['verified', 'payment'])->group(function () {
    Route::get('/cancelled', [CheckoutController::class, 'cancelled'])->name('checkout.cancelled');
    Route::get('/pending', [CheckoutController::class, 'pending'])->name('checkout.pending');
    Route::get('/complete', [CheckoutController::class, 'complete'])->name('checkout.complete');

    Route::get('/{id}', [CheckoutController::class, 'index'])->name('checkout.index');
    Route::post('/{id}', [CheckoutController::class, 'process']);
});

// Cronjob routes
Route::get('/cronjob', [CronjobController::class, 'index'])->name('cronjob');

// Webhook routes
Route::post('webhooks/paypal', [WebhookController::class, 'paypal'])->name('webhooks.paypal');
Route::post('webhooks/stripe', [WebhookController::class, 'stripe'])->name('webhooks.stripe');
Route::post('webhooks/paddle', [WebhookController::class, 'paddle'])->name('webhooks.paddle');
Route::post('webhooks/razorpay', [WebhookController::class, 'razorpay'])->name('webhooks.razorpay');
Route::post('webhooks/paystack', [WebhookController::class, 'paystack'])->name('webhooks.paystack');
Route::post('webhooks/cryptocom', [WebhookController::class, 'cryptocom'])->name('webhooks.cryptocom');
Route::post('webhooks/coinbase', [WebhookController::class, 'coinbase'])->name('webhooks.coinbase');

// Developer routes
Route::prefix('/developers')->group(function () {
    Route::get('/', [DeveloperController::class, 'index'])->name('developers');
    Route::get('/links', [DeveloperController::class, 'links'])->name('developers.links');
    Route::get('/spaces', [DeveloperController::class, 'spaces'])->name('developers.spaces');
    Route::get('/domains', [DeveloperController::class, 'domains'])->name('developers.domains');
    Route::get('/pixels', [DeveloperController::class, 'pixels'])->name('developers.pixels');
    Route::get('/stats', [DeveloperController::class, 'stats'])->name('developers.stats');
    Route::get('/account', [DeveloperController::class, 'account'])->name('developers.account');
});

// Redirect routes
Route::get('/{id}/+', [RedirectController::class, 'index'])->name('link.preview');
Route::get('/{id}', [RedirectController::class, 'index'])->name('link.redirect');
Route::post('/{id}/password', [RedirectController::class, 'validateRedirectPassword']);
Route::post('/{id}/tracking-consent', [RedirectController::class, 'validateTrackingConsent']);
Route::post('/{id}/sensitive-consent', [RedirectController::class, 'validateSensitiveConsent']);

Route::fallback(function () {
    return abort(404);
});
