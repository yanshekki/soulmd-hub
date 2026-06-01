<?php
/**
 * SoulMD Hub - Grand Unified Documentation Sub-module
 * Target: /docs/future (Ecosystem Tokenomics, Financial Ledger & DAO Governance)
 */
if (!defined('BASE_URL')) exit;
?>

<div class="space-y-12 animate-fade-in text-zinc-300">
    
    <div class="border-b border-white/10 pb-6">
        <h2 class="text-3xl sm:text-4xl font-black text-white tracking-tight mb-4 leading-tight">
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
                        <td class="p-5 font-bold text-emerald-400"><i class="fas fa-lightbulb mr-2"></i> <?= __('Alloc_Creator') ?></td>
                        <td class="p-5 font-black text-white text-lg"><?= __('Alloc_Creator_Pct') ?></td>
                        <td class="p-5 font-bold text-zinc-200"><?= __('Alloc_Creator_Amt') ?></td>
                        <td class="p-5 text-zinc-400 text-xs"><?= __('Alloc_Creator_Desc') ?></td>
                    </tr>
                    <tr class="hover:bg-purple-500/5 bg-black/20">
                        <td class="p-5 font-bold text-purple-400"><i class="fas fa-university mr-2"></i> <?= __('Alloc_Treasury') ?></td>
                        <td class="p-5 font-black text-white text-lg"><?= __('Alloc_Treasury_Pct') ?></td>
                        <td class="p-5 font-bold text-zinc-200"><?= __('Alloc_Treasury_Amt') ?></td>
                        <td class="p-5 text-zinc-400 text-xs"><?= __('Alloc_Treasury_Desc') ?></td>
                    </tr>
                    <tr class="hover:bg-purple-500/5 transition-colors">
                        <td class="p-5 font-bold text-blue-400"><i class="fas fa-user-tie mr-2"></i> <?= __('Alloc_Investor') ?></td>
                        <td class="p-5 font-black text-white text-lg"><?= __('Alloc_Investor_Pct') ?></td>
                        <td class="p-5 font-bold text-zinc-200"><?= __('Alloc_Investor_Amt') ?></td>
                        <td class="p-5 text-zinc-400 text-xs"><?= __('Alloc_Investor_Desc') ?></td>
                    </tr>
                    <tr class="hover:bg-purple-500/5 bg-black/20">
                        <td class="p-5 font-bold text-amber-400"><i class="fas fa-coins mr-2"></i> <?= __('Alloc_Staking') ?></td>
                        <td class="p-5 font-black text-white text-lg"><?= __('Alloc_Staking_Pct') ?></td>
                        <td class="p-5 font-bold text-zinc-200"><?= __('Alloc_Staking_Amt') ?></td>
                        <td class="p-5 text-zinc-400 text-xs"><?= __('Alloc_Staking_Desc') ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-gradient-to-b from-zinc-950 to-indigo-950/30 border border-indigo-500/20 rounded-3xl p-6 sm:p-8 shadow-2xl relative overflow-hidden">
        <div class="absolute top-0 right-0 w-64 h-64 bg-indigo-500/10 blur-3xl rounded-full"></div>
        <div class="flex items-center gap-4 mb-6 relative z-10">
            <h3 class="text-xl sm:text-2xl font-black text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 to-cyan-400">
                <?= __('AMM_Burn_Title') ?>
            </h3>
        </div>
        <p class="text-sm text-zinc-300 mb-8 relative z-10"><?= __('AMM_Burn_Desc') ?></p>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5 relative z-10">
            <div class="bg-black/40 border border-indigo-500/20 p-6 rounded-2xl">
                <div class="text-indigo-400 mb-4 font-bold"><i class="fas fa-hammer"></i> <?= __('Income_Mint') ?></div>
                <p class="text-xs text-zinc-400 font-mono"><?= __('Income_Mint_Desc') ?></p>
            </div>
            <div class="bg-black/40 border border-indigo-500/20 p-6 rounded-2xl">
                <div class="text-cyan-400 mb-4 font-bold"><i class="fas fa-exchange-alt"></i> <?= __('Income_Market') ?></div>
                <p class="text-xs text-zinc-400 font-mono"><?= __('Income_Market_Desc') ?></p>
            </div>
            <div class="bg-black/60 border border-pink-500/40 p-6 rounded-2xl shadow-[0_0_30px_rgba(236,72,153,0.15)] md:transform md:-translate-y-3">
                <div class="text-pink-400 mb-4 font-black animate-pulse"><i class="fas fa-sync-alt fa-spin"></i> <?= __('Income_Burn') ?></div>
                <p class="text-xs text-zinc-200 font-mono"><?= __('Income_Burn_Desc') ?></p>
            </div>
        </div>
    </div>

    <div class="bg-zinc-950/40 border border-emerald-500/20 rounded-3xl p-6 sm:p-8 shadow-inner">
        <h3 class="text-xl font-bold text-white mb-4"><i class="fas fa-code text-emerald-400 mr-2"></i><?= __('NFT_Matrix_Title') ?></h3>
        <p class="text-sm text-zinc-400 mb-6"><?= __('NFT_Matrix_Desc') ?></p>
        
        <pre class="bg-[#0d1117] p-5 rounded-2xl text-xs sm:text-sm font-mono overflow-x-auto border border-white/10 shadow-2xl">
<span class="text-pink-400">{</span>
  <span class="text-blue-300">"token_id"</span><span class="text-pink-400">:</span> <span class="text-amber-300">"agent-soul-1024"</span><span class="text-zinc-400">,</span>
  <span class="text-blue-300">"owner_id"</span><span class="text-pink-400">:</span> <span class="text-amber-300">"creator_wallet.near"</span><span class="text-zinc-400">,</span>
  <span class="text-zinc-500 italic">// <?= __('JSON_Comment_Metadata') ?></span>
  <span class="text-blue-300">"metadata"</span><span class="text-pink-400">:</span> <span class="text-pink-400">{</span>
    <span class="text-blue-300">"title"</span><span class="text-pink-400">:</span> <span class="text-amber-300">"<?= __('JSON_Value_Title') ?>"</span><span class="text-zinc-400">,</span>
    <span class="text-blue-300">"description"</span><span class="text-pink-400">:</span> <span class="text-amber-300">"<?= __('JSON_Value_Desc') ?>"</span><span class="text-zinc-400">,</span>
    
    <span class="text-zinc-500 italic">// <?= __('JSON_Comment_Extra') ?></span>
    <span class="text-blue-300">"extra"</span><span class="text-pink-400">:</span> <span class="text-pink-400">{</span>
      <span class="text-blue-300">"prompt_hash"</span><span class="text-pink-400">:</span> <span class="text-emerald-300">"sha256:8d969eef6ecad3c...a86a"</span><span class="text-zinc-400">,</span>
      
      <span class="text-zinc-500 italic">// <?= __('JSON_Comment_Royalty') ?></span>
      <span class="text-blue-300">"royalty_tree"</span><span class="text-pink-400">:</span> <span class="text-pink-400">{</span>
        <span class="text-blue-300">"base_model.near"</span><span class="text-pink-400">:</span> <span class="text-purple-400">500</span><span class="text-zinc-400">,</span>  <span class="text-zinc-500 italic">// 5%</span>
        <span class="text-blue-300">"creator.near"</span><span class="text-pink-400">:</span> <span class="text-purple-400">9500</span>    <span class="text-zinc-500 italic">// 95%</span>
      <span class="text-pink-400">}</span>
    <span class="text-pink-400">}</span>
  <span class="text-pink-400">}</span>
<span class="text-pink-400">}</span></pre>
    </div>

    <div class="bg-zinc-950/40 border border-amber-500/20 rounded-3xl p-6 sm:p-8 shadow-inner">
        <h3 class="text-xl font-bold text-white mb-4"><i class="fas fa-users-cog text-amber-400 mr-2"></i><?= __('DAO_Gov_Title') ?></h3>
        <p class="text-sm text-zinc-400 mb-6"><?= __('DAO_Gov_Desc') ?></p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-zinc-900/40 border border-white/5 p-6 rounded-2xl">
                <h4 class="text-white font-bold mb-3"><i class="fas fa-sliders-h text-amber-400 mr-2"></i><?= __('DAO_Prop_1') ?></h4>
                <p class="text-xs text-zinc-400"><?= __('DAO_Prop_1_Desc') ?></p>
            </div>
            <div class="bg-zinc-900/40 border border-white/5 p-6 rounded-2xl">
                <h4 class="text-white font-bold mb-3"><i class="fas fa-microchip text-amber-400 mr-2"></i><?= __('DAO_Prop_2') ?></h4>
                <p class="text-xs text-zinc-400"><?= __('DAO_Prop_2_Desc') ?></p>
            </div>
        </div>
    </div>
</div>