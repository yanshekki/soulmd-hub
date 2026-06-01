<?php
/**
 * SoulMD Hub - Grand Unified Documentation Sub-module
 * Target: /docs/future (Ecosystem Tokenomics & Whitepaper)
 * 🚀 Fully synchronized with contract/src/contract.ts mechanics.
 */
if (!defined('BASE_URL')) exit;
?>

<div class="space-y-12 animate-fade-in text-zinc-300">
    
    <div class="border-b border-white/10 pb-6">
        <h2 class="text-3xl sm:text-4xl font-black text-white tracking-tight mb-4 leading-tight text-transparent bg-clip-text bg-gradient-to-r from-purple-400 to-indigo-400">
            <?= __('AgentFi_Whitepaper_Title') ?>
        </h2>
        <p class="text-sm sm:text-base leading-relaxed max-w-5xl text-zinc-400">
            <?= __('AgentFi_Whitepaper_Desc') ?>
        </p>
    </div>

    <div class="bg-zinc-950/40 border border-purple-500/20 rounded-3xl p-6 sm:p-8 md:p-10 shadow-inner">
        <h3 class="text-xl sm:text-2xl font-bold text-white mb-4 flex items-center gap-3">
            <i class="fas fa-chart-pie text-purple-400 bg-purple-500/10 p-2.5 rounded-xl border border-purple-500/20"></i>
            <?= __('Tokenomics_Allocation_Title') ?>
        </h3>
        <p class="text-sm text-zinc-400 mb-8 leading-relaxed"><?= __('Tokenomics_Allocation_Desc') ?></p>

        <div class="overflow-x-auto border border-white/10 rounded-2xl bg-zinc-950/50 shadow-2xl">
            <table class="w-full text-left border-collapse font-mono text-sm">
                <thead>
                    <tr class="bg-black/40 border-b border-white/10 text-zinc-400 uppercase tracking-wider text-xs">
                        <th class="p-5 font-bold w-1/4">Segment</th>
                        <th class="p-5 font-bold w-1/6">Percentage</th>
                        <th class="p-5 font-bold w-1/4">Supply</th>
                        <th class="p-5 font-bold">Vesting & Utility</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5 text-zinc-300">
                    <tr class="hover:bg-purple-500/5 transition-colors">
                        <td class="p-5 font-bold text-emerald-400 flex items-center gap-2">
                            <i class="fas fa-user-astronaut text-base"></i> <?= __('Alloc_Founder') ?>
                        </td>
                        <td class="p-5 font-black text-white text-lg"><?= __('Alloc_Founder_Pct') ?></td>
                        <td class="p-5 font-bold text-zinc-200"><?= __('Alloc_Founder_Amt') ?></td>
                        <td class="p-5 text-zinc-400 text-xs leading-relaxed"><?= __('Alloc_Founder_Desc') ?></td>
                    </tr>
                    <tr class="hover:bg-purple-500/5 bg-black/20">
                        <td class="p-5 font-bold text-blue-400 flex items-center gap-2">
                            <i class="fas fa-user-tie text-base"></i> <?= __('Alloc_Investor') ?>
                        </td>
                        <td class="p-5 font-black text-white text-lg"><?= __('Alloc_Investor_Pct') ?></td>
                        <td class="p-5 font-bold text-zinc-200"><?= __('Alloc_Investor_Amt') ?></td>
                        <td class="p-5 text-zinc-400 text-xs leading-relaxed"><?= __('Alloc_Investor_Desc') ?></td>
                    </tr>
                    <tr class="hover:bg-purple-500/5 transition-colors">
                        <td class="p-5 font-bold text-purple-400 flex items-center gap-2">
                            <i class="fas fa-university text-base"></i> <?= __('Alloc_Treasury') ?>
                        </td>
                        <td class="p-5 font-black text-white text-lg"><?= __('Alloc_Treasury_Pct') ?></td>
                        <td class="p-5 font-bold text-zinc-200"><?= __('Alloc_Treasury_Amt') ?></td>
                        <td class="p-5 text-zinc-400 text-xs leading-relaxed"><?= __('Alloc_Treasury_Desc') ?></td>
                    </tr>
                    <tr class="hover:bg-purple-500/5 bg-black/20">
                        <td class="p-5 font-bold text-amber-400 flex items-center gap-2">
                            <i class="fas fa-coins text-base"></i> <?= __('Alloc_Staking') ?>
                        </td>
                        <td class="p-5 font-black text-white text-lg"><?= __('Alloc_Staking_Pct') ?></td>
                        <td class="p-5 font-bold text-zinc-200"><?= __('Alloc_Staking_Amt') ?></td>
                        <td class="p-5 text-zinc-400 text-xs leading-relaxed"><?= __('Alloc_Staking_Desc') ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-zinc-950/40 border border-emerald-500/20 rounded-3xl p-6 sm:p-8 md:p-10 shadow-inner">
        <h3 class="text-xl sm:text-2xl font-bold text-white mb-4 flex items-center gap-3">
            <i class="fas fa-file-contract text-emerald-400 bg-emerald-500/10 p-2.5 rounded-xl border border-emerald-500/20"></i>
            <?= __('Revenue_Stream_Title') ?>
        </h3>
        <p class="text-sm text-zinc-400 mb-8 leading-relaxed"><?= __('Revenue_Stream_Desc') ?></p>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div class="bg-zinc-900/40 border border-white/5 p-5 rounded-2xl">
                <div class="text-emerald-400 mb-2 font-bold font-mono">function mint_soul()</div>
                <div class="text-white font-bold mb-2"><?= __('Rev_Mint') ?></div>
                <p class="text-xs text-zinc-400 leading-relaxed"><?= __('Rev_Mint_Desc') ?></p>
            </div>
            <div class="bg-zinc-900/40 border border-white/5 p-5 rounded-2xl">
                <div class="text-emerald-400 mb-2 font-bold font-mono">function buy_soul()</div>
                <div class="text-white font-bold mb-2"><?= __('Rev_Buy') ?></div>
                <p class="text-xs text-zinc-400 leading-relaxed"><?= __('Rev_Buy_Desc') ?></p>
            </div>
            <div class="bg-zinc-900/40 border border-white/5 p-5 rounded-2xl">
                <div class="text-emerald-400 mb-2 font-bold font-mono">function rent_soul()</div>
                <div class="text-white font-bold mb-2"><?= __('Rev_Rent') ?></div>
                <p class="text-xs text-zinc-400 leading-relaxed"><?= __('Rev_Rent_Desc') ?></p>
            </div>
            <div class="bg-zinc-900/40 border border-white/5 p-5 rounded-2xl">
                <div class="text-emerald-400 mb-2 font-bold font-mono">function burn_soul()</div>
                <div class="text-white font-bold mb-2"><?= __('Rev_Burn') ?></div>
                <p class="text-xs text-zinc-400 leading-relaxed"><?= __('Rev_Burn_Desc') ?></p>
            </div>
        </div>
    </div>

    <div class="bg-gradient-to-b from-zinc-950 to-indigo-950/30 border border-indigo-500/20 rounded-3xl p-6 sm:p-8 md:p-10 shadow-2xl relative overflow-hidden">
        <div class="absolute top-0 right-0 w-64 h-64 bg-indigo-500/10 blur-3xl rounded-full pointer-events-none"></div>
        <div class="flex items-center gap-4 mb-6 relative z-10">
            <div class="w-14 h-14 bg-indigo-500/10 border border-indigo-500/30 rounded-2xl flex items-center justify-center text-indigo-400 shrink-0 shadow-lg shadow-indigo-500/10">
                <i class="fas fa-sync-alt fa-spin text-2xl"></i>
            </div>
            <h3 class="text-xl sm:text-2xl font-black text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 to-cyan-400">
                <?= __('AMM_Burn_Title') ?>
            </h3>
        </div>
        <p class="text-sm text-zinc-300 mb-8 relative z-10 leading-relaxed"><?= __('AMM_Burn_Desc') ?></p>
        
        <div class="bg-black/60 border border-pink-500/40 p-6 sm:p-8 rounded-2xl shadow-[0_0_30px_rgba(236,72,153,0.15)] relative z-10">
            <div class="text-pink-400 mb-4 font-black text-lg animate-pulse flex items-center gap-3">
                <i class="fas fa-fire-alt text-xl"></i> <?= __('Burn_Execution_Title') ?>
            </div>
            <p class="text-sm text-zinc-200 font-mono leading-relaxed bg-black/40 p-4 rounded-xl border border-white/5">
                <?= __('Burn_Execution_Desc') ?>
            </p>
        </div>
    </div>

    <div class="bg-zinc-950/40 border border-red-500/20 rounded-3xl p-6 sm:p-8 md:p-10 shadow-inner">
        <h3 class="text-xl sm:text-2xl font-bold text-white mb-4 flex items-center gap-3">
            <i class="fas fa-shield-alt text-red-400 bg-red-500/10 p-2.5 rounded-xl border border-red-500/20"></i>
            <?= __('Anti_Rug_Title') ?>
        </h3>
        <p class="text-sm text-zinc-400 mb-6 leading-relaxed"><?= __('Anti_Rug_Desc') ?></p>
        
        <div class="bg-zinc-900/40 border border-red-500/20 p-6 rounded-2xl border-l-4 border-l-red-500">
            <h4 class="text-white font-bold mb-2 flex items-center gap-2">
                <i class="fas fa-lock text-red-400"></i> <?= __('Anti_Rug_Logic') ?>
            </h4>
            <p class="text-xs sm:text-sm text-zinc-400 leading-relaxed">
                <?= __('Anti_Rug_Logic_Desc') ?>
            </p>
        </div>
    </div>

    <div class="bg-zinc-950/40 border border-amber-500/20 rounded-3xl p-6 sm:p-8 md:p-10 shadow-inner">
        <h3 class="text-xl sm:text-2xl font-bold text-white mb-4 flex items-center gap-3">
            <i class="fas fa-users-cog text-amber-400 bg-amber-500/10 p-2.5 rounded-xl border border-amber-500/20"></i>
            <?= __('DAO_Gov_Title') ?>
        </h3>
        <p class="text-sm text-zinc-400 mb-6 leading-relaxed"><?= __('DAO_Gov_Desc') ?></p>

        <div class="bg-zinc-900/40 border border-white/5 p-6 rounded-2xl shadow-md">
            <h4 class="text-white font-bold mb-3 flex items-center gap-2"><i class="fas fa-sliders-h text-amber-400 text-xs"></i> <?= __('DAO_Prop') ?></h4>
            <p class="text-xs sm:text-sm text-zinc-400 leading-relaxed"><?= __('DAO_Prop_Desc') ?></p>
        </div>
    </div>

</div>