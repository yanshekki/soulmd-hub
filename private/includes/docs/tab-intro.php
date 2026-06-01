<?php
/**
 * SoulMD Hub - Documentation Sub-module
 * Target: /docs?tab=intro
 * Concept: Introduction, Modular Architecture & Dual Engine Matrix
 */

// 確保此檔案不被直接存取，必須由 docs.php 引入
if (!defined('BASE_URL')) {
    header("HTTP/1.0 404 Not Found");
    exit;
}
?>

<div class="space-y-8">
    
    <div>
        <h2 class="text-3xl sm:text-4xl font-extrabold text-white tracking-tight mb-3"><?= __('Intro Title') ?></h2>
        <p class="text-sm sm:text-base text-zinc-400 leading-relaxed max-w-4xl"><?= __('Intro Desc') ?></p>
    </div>

    <div class="bg-zinc-950/50 border border-white/5 rounded-3xl p-6 sm:p-8 shadow-inner hover:border-white/10 transition-colors duration-300">
        <h3 class="text-xl sm:text-2xl font-bold text-emerald-400 mb-3 flex items-center gap-3">
            <i class="fas fa-layer-group bg-emerald-500/10 p-2 rounded-lg"></i> <?= __('Core Prompt Modularization') ?>
        </h3>
        <p class="text-sm text-zinc-400 mb-8 leading-relaxed"><?= __('Core Prompt Desc') ?></p>
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
            <div class="bg-zinc-900/80 border border-emerald-500/20 p-6 rounded-2xl shadow-lg relative overflow-hidden group hover:border-emerald-500/40 transition-all">
                <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-emerald-500 to-transparent"></div>
                <div class="text-emerald-400 mb-4 transform group-hover:scale-110 transition duration-300 origin-left"><i class="fas fa-brain text-3xl"></i></div>
                <div class="text-sm text-zinc-300 leading-relaxed"><?= __('Layout Soul') ?></div>
            </div>
            
            <div class="bg-zinc-900/80 border border-purple-500/20 p-6 rounded-2xl shadow-lg relative overflow-hidden group hover:border-purple-500/40 transition-all">
                <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-purple-500 to-transparent"></div>
                <div class="text-purple-400 mb-4 transform group-hover:scale-110 transition duration-300 origin-left"><i class="fas fa-palette text-3xl"></i></div>
                <div class="text-sm text-zinc-300 leading-relaxed"><?= __('Layout Style') ?></div>
            </div>
            
            <div class="bg-zinc-900/80 border border-red-500/20 p-6 rounded-2xl shadow-lg relative overflow-hidden group hover:border-red-500/40 transition-all">
                <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-red-500 to-transparent"></div>
                <div class="text-red-400 mb-4 transform group-hover:scale-110 transition duration-300 origin-left"><i class="fas fa-shield-alt text-3xl"></i></div>
                <div class="text-sm text-zinc-300 leading-relaxed"><?= __('Layout Rules') ?></div>
            </div>
        </div>
    </div>

    <div class="bg-zinc-950/50 border border-white/5 rounded-3xl p-6 sm:p-8 shadow-inner hover:border-white/10 transition-colors duration-300">
        <h3 class="text-xl sm:text-2xl font-bold text-blue-400 mb-3 flex items-center gap-3">
            <i class="fas fa-project-diagram bg-blue-500/10 p-2 rounded-lg"></i> <?= __('Dual Engine Matrix') ?>
        </h3>
        <p class="text-sm text-zinc-400 mb-8 leading-relaxed"><?= __('Dual Engine Desc') ?></p>
        
        <div class="space-y-4">
            <div class="flex flex-col sm:flex-row items-start gap-4 sm:gap-6 bg-zinc-900/80 border border-white/5 p-5 sm:p-6 rounded-2xl shadow-lg">
                <div class="w-14 h-14 rounded-2xl bg-blue-500/10 border border-blue-500/20 flex items-center justify-center text-blue-400 shrink-0">
                    <i class="fas fa-comment-dots text-2xl"></i>
                </div>
                <div class="text-sm text-zinc-300 leading-relaxed sm:pt-1">
                    <?= __('Route Text') ?>
                </div>
            </div>
            
            <div class="flex flex-col sm:flex-row items-start gap-4 sm:gap-6 bg-zinc-900/80 border border-white/5 p-5 sm:p-6 rounded-2xl shadow-lg">
                <div class="w-14 h-14 rounded-2xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 shrink-0">
                    <i class="fas fa-eye text-2xl"></i>
                </div>
                <div class="text-sm text-zinc-300 leading-relaxed sm:pt-1">
                    <?= __('Route Vision') ?>
                </div>
            </div>
        </div>
    </div>

</div>