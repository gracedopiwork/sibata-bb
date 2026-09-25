<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LegalCaseController;
use App\Http\Controllers\PhysicalUnitController;
use App\Http\Controllers\PrintLabelController;
use App\Http\Controllers\PublicUnitController;
use App\Http\Controllers\SipReportController;
use App\Http\Controllers\BotSettingsController;
use App\Http\Controllers\TelegramWhitelistController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProsecutorController;
use App\Http\Controllers\CaseTypeController;
use App\Http\Controllers\EvidenceCategoryController;
use App\Http\Controllers\AssetTypeController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('view/{unit_code}', [PublicUnitController::class, 'show'])
    ->name('units.public');

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);
});

Route::middleware(['auth', 'active'])->group(function () {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::middleware('role:admin')->group(function () {
        Route::get('dashboard', DashboardController::class)->name('dashboard');

        Route::resource('cases', LegalCaseController::class)->except(['destroy']);

        Route::get('units', [PhysicalUnitController::class, 'index'])->name('units.index');
        Route::get('units/{unit}', [PhysicalUnitController::class, 'show'])->name('units.show');
        Route::post('units/{unit}/loan', [PhysicalUnitController::class, 'loan'])->name('units.loan');
        Route::post('units/{unit}/return', [PhysicalUnitController::class, 'returnToWarehouse'])->name('units.return');
        Route::post('units/{unit}/children', [PhysicalUnitController::class, 'addChild'])->name('units.children.store');
        Route::post('units/{unit}/items/{item}/execute', [PhysicalUnitController::class, 'execute'])->name('units.items.execute');

        Route::get('print-labels', [PrintLabelController::class, 'index'])->name('print-labels.index');
        Route::get('print-labels/sheet', [PrintLabelController::class, 'sheet'])->name('print-labels.sheet');
        Route::post('print-labels/printed', [PrintLabelController::class, 'markPrinted'])->name('print-labels.printed');

        Route::get('reports', [SipReportController::class, 'index'])->name('reports.index');
        Route::get('reports/excel', [SipReportController::class, 'excel'])->name('reports.excel');

        Route::resource('whitelist', TelegramWhitelistController::class)->except(['show']);
        Route::get('bot', [BotSettingsController::class, 'edit'])->name('bot.edit');
        Route::post('bot', [BotSettingsController::class, 'update'])->name('bot.update');
        Route::resource('users', UserController::class)->except(['show']);
        Route::resource('prosecutors', ProsecutorController::class)->except(['show', 'create']);
        Route::resource('case-types', CaseTypeController::class)->except(['show', 'create']);
        Route::resource('evidence-categories', EvidenceCategoryController::class)->except(['show', 'create']);
        Route::resource('asset-types', AssetTypeController::class)->except(['show', 'create']);
        Route::post('users/{user}/license/regenerate', [UserController::class, 'regenerateLicense'])->name('users.license.regenerate');
        Route::post('users/{user}/license/revoke', [UserController::class, 'revokeLicense'])->name('users.license.revoke');
        Route::post('users/{user}/license/restore', [UserController::class, 'restoreLicense'])->name('users.license.restore');
    });
});
