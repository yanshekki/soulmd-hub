<?php
/**
 * SoulMD Hub - Grand Unified Documentation Sub-module
 * Target: /docs/solutions
 * Layout: Enterprise Deep-Dive Mitigation & Architectural Solutions Grid
 * 🚀 Patched: 100% Pure Brand-Agnostic Edition & Fully i18n Compliant.
 */

// 確保此檔案不被直接存取，必須由 docs.php 引入
if (!defined('BASE_URL')) {
    header("HTTP/1.0 404 Not Found");
    exit;
}
?>

<div class="space-y-12 animate-fade-in">
    
    <div class="border-b border-white/10 pb-6">
        <h2 class="text-2xl sm:text-3xl font-black text-white tracking-tight mb-4">
            <?= __('Enterprise Security & Mitigation Engineering') ?>
        </h2>
        <p class="text-zinc-400 text-sm sm:text-base leading-relaxed max-w-5xl">
            <?= __('Solutions Core Intro') ?>
        </p>
    </div>

    <div class="bg-zinc-950/40 border border-white/5 rounded-3xl p-6 sm:p-8 md:p-10 shadow-inner hover:border-emerald-500/20 transition-all duration-300 relative overflow-hidden group">
        <div class="absolute top-0 left-0 w-1 h-full bg-emerald-500"></div>
        
        <h3 class="text-xl sm:text-2xl font-bold text-white mb-4 flex items-center gap-3 select-none">
            <i class="fas fa-key text-emerald-400 bg-emerald-500/10 p-2.5 rounded-xl border border-emerald-500/20 text-base sm:text-lg"></i>
            <?= __('Stateless BYOK Proxy') ?>
        </h3>
        
        <div class="text-zinc-400 text-xs sm:text-sm space-y-4 leading-relaxed mb-6">
            <p>
                <strong><?= __('Problem Analysis:') ?></strong> <?= __('BYOK Problem Analysis') ?>
            </p>
            <p>
                <strong><?= __('Core Solution:') ?></strong> <?= __('BYOK Solution') ?>
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-zinc-900/50 border border-white/5 p-5 sm:p-6 rounded-2xl shadow-md">
                <h4 class="text-white font-bold text-sm sm:text-base mb-3 flex items-center gap-2 font-mono">
                    <i class="fas fa-cipher text-emerald-400 text-xs"></i> <?= __('Symmetric Encryption Storage') ?>
                </h4>
                <p class="text-xs sm:text-sm text-zinc-400 leading-relaxed font-mono bg-black/30 p-3 rounded-xl border border-white/5">
                    <?= __('Symmetric Encryption Desc') ?>
                </p>
            </div>
            
            <div class="bg-zinc-900/50 border border-white/5 p-5 sm:p-6 rounded-2xl shadow-md">
                <h4 class="text-white font-bold text-sm sm:text-base mb-3 flex items-center gap-2 font-mono">
                    <i class="fas fa-memory text-emerald-400 text-xs"></i> <?= __('Ephemeral Memory Decoupling') ?>
                </h4>
                <p class="text-xs sm:text-sm text-zinc-400 leading-relaxed font-mono bg-black/30 p-3 rounded-xl border border-white/5">
                    <?= __('Ephemeral Memory Desc') ?>
                </p>
            </div>
        </div>
    </div>

    <div class="bg-zinc-950/40 border border-white/5 rounded-3xl p-6 sm:p-8 md:p-10 shadow-inner hover:border-blue-500/20 transition-all duration-300 relative overflow-hidden group">
        <div class="absolute top-0 left-0 w-1 h-full bg-blue-500"></div>
        
        <h3 class="text-xl sm:text-2xl font-bold text-white mb-4 flex items-center gap-3 select-none">
            <i class="fas fa-compress-arrows-alt text-blue-400 bg-blue-500/10 p-2.5 rounded-xl border border-blue-500/20 text-base sm:text-lg"></i>
            <?= __('GPU Canvas Compression') ?>
        </h3>
        
        <div class="text-zinc-400 text-xs sm:text-sm space-y-4 leading-relaxed mb-6">
            <p>
                <strong><?= __('Problem Analysis:') ?></strong> <?= __('Canvas Problem Analysis') ?>
            </p>
            <p>
                <strong><?= __('Core Solution:') ?></strong> <?= __('Canvas Solution') ?>
            </p>
        </div>

        <div class="bg-zinc-900/50 border border-white/5 p-5 sm:p-6 rounded-2xl font-mono text-xs sm:text-sm leading-relaxed text-zinc-300 space-y-3 shadow-md">
            <div class="flex items-center gap-2 border-b border-white/5 pb-2 text-blue-400 font-bold">
                <i class="fas fa-code"></i> <?= __('Headless Canvas Interceptor') ?>
            </div>
            <p><?= __('Canvas Code Desc') ?></p>
            <pre class="bg-zinc-950 p-4 rounded-xl text-cyan-300 overflow-x-auto border border-white/5 leading-tight">
// System Hardcoded Golden Matrix
const IMAGE_MAX_DIMENSION = 800; // Max boundary constraint
const IMAGE_QUALITY = 0.6;       // Bicubic interpolation sampling rate

// Enable GPU Hardware Acceleration
let ctx = canvas.getContext('2d');
ctx.drawImage(img, 0, 0, targetWidth, targetHeight);
let base64Payload = canvas.toDataURL('image/jpeg', IMAGE_QUALITY);</pre>
            <p class="text-zinc-400 pt-2">
                <strong><?= __('Architecture Effect:') ?></strong> <?= __('Canvas Architecture Effect') ?>
            </p>
        </div>
    </div>

    <div class="bg-zinc-950/40 border border-white/5 rounded-3xl p-6 sm:p-8 md:p-10 shadow-inner hover:border-purple-500/20 transition-all duration-300 relative overflow-hidden group">
        <div class="absolute top-0 left-0 w-1 h-full bg-purple-500"></div>
        
        <h3 class="text-xl sm:text-2xl font-bold text-white mb-4 flex items-center gap-3 select-none">
            <i class="fas fa-compress text-purple-400 bg-purple-500/10 p-2.5 rounded-xl border border-purple-500/20 text-base sm:text-lg"></i>
            <?= __('Deterministic Sliding Memory') ?>
        </h3>
        
        <div class="text-zinc-400 text-xs sm:text-sm space-y-4 leading-relaxed mb-6">
            <p>
                <strong><?= __('Problem Analysis:') ?></strong> <?= __('Memory Problem Analysis') ?>
            </p>
            <p>
                <strong><?= __('Core Solution:') ?></strong> <?= __('Memory Solution') ?>
            </p>
        </div>

        <div class="bg-zinc-900/40 border border-white/5 p-6 rounded-2xl shadow-inner relative">
            <div class="flex flex-col lg:flex-row gap-6 items-start">
                <div class="flex-1 space-y-4">
                    <h4 class="text-white font-bold text-base flex items-center gap-2">
                        <i class="fas fa-network-wired text-purple-400 text-xs"></i> <?= __('Deterministic Threshold Algorithm') ?>
                    </h4>
                    <ul class="list-none space-y-3 text-xs sm:text-sm text-zinc-400 font-mono">
                        <li class="flex items-start gap-2"><span class="text-purple-400 font-black">▶</span> <?= __('Memory Step A') ?></li>
                        <li class="flex items-start gap-2"><span class="text-purple-400 font-black">▶</span> <?= __('Memory Step B') ?></li>
                        <li class="flex items-start gap-2"><span class="text-purple-400 font-black">▶</span> <?= __('Memory Step C') ?></li>
                        <li class="flex items-start gap-2"><span class="text-purple-400 font-black">▶</span> <?= __('Memory Step D') ?></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-zinc-950/40 border border-white/5 rounded-3xl p-6 sm:p-8 md:p-10 shadow-inner hover:border-red-500/20 transition-all duration-300 relative overflow-hidden group">
        <div class="absolute top-0 left-0 w-1 h-full bg-red-500"></div>
        
        <h3 class="text-xl sm:text-2xl font-bold text-white mb-4 flex items-center gap-3 select-none">
            <i class="fas fa-fingerprint text-red-400 bg-red-500/10 p-2.5 rounded-xl border border-red-500/20 text-base sm:text-lg"></i>
            <?= __('Off-Chain Fingerprint') ?>
        </h3>
        
        <div class="text-zinc-400 text-xs sm:text-sm space-y-4 leading-relaxed">
            <p>
                <strong><?= __('Problem Analysis:') ?></strong> <?= __('Fingerprint Problem Analysis') ?>
            </p>
            <p>
                <strong><?= __('Core Solution:') ?></strong> <?= __('Fingerprint Solution') ?>
            </p>
            <div class="bg-zinc-900/50 border border-white/5 p-5 rounded-2xl font-mono text-xs sm:text-sm text-zinc-300 shadow-md">
                <?= __('Fingerprint Desc') ?>
            </div>
        </div>
    </div>

</div>