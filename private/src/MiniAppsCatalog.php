<?php
/**
 * SoulMD Hub - Curated Mini Apps catalog (form-driven tools).
 * Maps slug → form schema + MINI_APP_SOUL_MAP (int|int[] of public soul ids).
 * Users pick a soul on /apps then continue in /chat (no in-app LLM run).
 */

class MiniAppsCatalog
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function allRaw(): array
    {
        return [
            [
                'slug' => 'name-advisor',
                'icon' => 'fa-signature',
                'category' => 'destiny',
                'title_key' => 'app_name_advisor_title',
                'desc_key' => 'app_name_advisor_desc',
                'sort_order' => 10,
                'enabled' => true,
                'badge' => 'popular',
                'builtin_prompt' => "You are an expert Chinese naming consultant (改名 / 命名顧問). Use Five Elements (五行), character structure, and Cantonese/Mandarin phonetics when relevant. Be concrete: propose ranked name options with brief rationale. Prefer tables when listing multiple names. Respond in the user's language.",
                'fields' => [
                    ['name' => 'surname', 'type' => 'text', 'label_key' => 'field_surname', 'placeholder_key' => 'ph_surname', 'required' => true, 'maxlength' => 20],
                    ['name' => 'gender', 'type' => 'select', 'label_key' => 'field_gender', 'required' => true, 'options' => [
                        ['value' => 'female', 'label_key' => 'opt_female'],
                        ['value' => 'male', 'label_key' => 'opt_male'],
                        ['value' => 'neutral', 'label_key' => 'opt_neutral'],
                    ]],
                    ['name' => 'birth_datetime', 'type' => 'text', 'label_key' => 'field_birth_datetime', 'placeholder_key' => 'ph_birth_datetime', 'required' => true, 'maxlength' => 40],
                    ['name' => 'preferences', 'type' => 'textarea', 'label_key' => 'field_name_preferences', 'placeholder_key' => 'ph_name_preferences', 'required' => false, 'maxlength' => 200],
                    ['name' => 'count', 'type' => 'select', 'label_key' => 'field_name_count', 'required' => false, 'options' => [
                        ['value' => '3', 'label_key' => 'opt_count_3'],
                        ['value' => '5', 'label_key' => 'opt_count_5'],
                    ]],
                ],
            ],
            [
                'slug' => 'feng-shui',
                'icon' => 'fa-compass',
                'category' => 'destiny',
                'title_key' => 'app_feng_shui_title',
                'desc_key' => 'app_feng_shui_desc',
                'sort_order' => 20,
                'enabled' => true,
                'badge' => null,
                'builtin_prompt' => "You are a practical feng shui consultant (風水顧問). Give actionable, safety-aware advice for homes/offices. Structure: overall assessment, priority fixes, and what to avoid. Respond in the user's language. Avoid absolute medical/financial claims.",
                'fields' => [
                    ['name' => 'space_type', 'type' => 'select', 'label_key' => 'field_space_type', 'required' => true, 'options' => [
                        ['value' => 'apartment', 'label_key' => 'opt_apartment'],
                        ['value' => 'house', 'label_key' => 'opt_house'],
                        ['value' => 'office', 'label_key' => 'opt_office'],
                        ['value' => 'shop', 'label_key' => 'opt_shop'],
                    ]],
                    ['name' => 'orientation', 'type' => 'text', 'label_key' => 'field_orientation', 'placeholder_key' => 'ph_orientation', 'required' => false, 'maxlength' => 40],
                    ['name' => 'floor_layout', 'type' => 'textarea', 'label_key' => 'field_floor_layout', 'placeholder_key' => 'ph_floor_layout', 'required' => true, 'maxlength' => 300],
                    ['name' => 'concern', 'type' => 'textarea', 'label_key' => 'field_feng_concern', 'placeholder_key' => 'ph_feng_concern', 'required' => true, 'maxlength' => 200],
                ],
            ],
            [
                'slug' => 'wedding-date',
                'icon' => 'fa-heart',
                'category' => 'life',
                'title_key' => 'app_wedding_date_title',
                'desc_key' => 'app_wedding_date_desc',
                'sort_order' => 30,
                'enabled' => true,
                'badge' => null,
                'builtin_prompt' => "You are a traditional Chinese auspicious date consultant for weddings (擇日結婚). Propose several candidate dates with short rationale (天干地支 / 宜忌 style when useful). Be respectful and note cultural tradition is advisory only. Respond in the user's language.",
                'fields' => [
                    ['name' => 'person_a_birth', 'type' => 'text', 'label_key' => 'field_person_a_birth', 'placeholder_key' => 'ph_birth_datetime', 'required' => true, 'maxlength' => 40],
                    ['name' => 'person_b_birth', 'type' => 'text', 'label_key' => 'field_person_b_birth', 'placeholder_key' => 'ph_birth_datetime', 'required' => true, 'maxlength' => 40],
                    ['name' => 'preferred_months', 'type' => 'text', 'label_key' => 'field_preferred_months', 'placeholder_key' => 'ph_preferred_months', 'required' => false, 'maxlength' => 60],
                    ['name' => 'region', 'type' => 'text', 'label_key' => 'field_region', 'placeholder_key' => 'ph_region', 'required' => false, 'maxlength' => 40],
                    ['name' => 'notes', 'type' => 'textarea', 'label_key' => 'field_wedding_notes', 'placeholder_key' => 'ph_wedding_notes', 'required' => false, 'maxlength' => 200],
                ],
            ],
            [
                'slug' => 'virtual-companion',
                'icon' => 'fa-heart',
                'category' => 'emotion',
                'title_key' => 'app_virtual_companion_title',
                'desc_key' => 'app_virtual_companion_desc',
                'sort_order' => 40,
                'enabled' => true,
                'badge' => 'hot',
                'builtin_prompt' => "You are a warm, respectful virtual companion roleplay partner. Stay in character, match the requested tone, and keep interactions safe/consensual for adults. Do not claim to be a real human. Respond in the user's language.",
                'fields' => [
                    ['name' => 'persona_style', 'type' => 'select', 'label_key' => 'field_persona_style', 'required' => true, 'options' => [
                        ['value' => 'gentle', 'label_key' => 'opt_gentle'],
                        ['value' => 'playful', 'label_key' => 'opt_playful'],
                        ['value' => 'intellectual', 'label_key' => 'opt_intellectual'],
                        ['value' => 'protective', 'label_key' => 'opt_protective'],
                    ]],
                    ['name' => 'tone', 'type' => 'select', 'label_key' => 'field_tone', 'required' => true, 'options' => [
                        ['value' => 'cantonese', 'label_key' => 'opt_cantonese'],
                        ['value' => 'mandarin', 'label_key' => 'opt_mandarin'],
                        ['value' => 'english', 'label_key' => 'opt_english'],
                    ]],
                    ['name' => 'scenario', 'type' => 'textarea', 'label_key' => 'field_scenario', 'placeholder_key' => 'ph_scenario', 'required' => true, 'maxlength' => 300],
                    ['name' => 'message', 'type' => 'textarea', 'label_key' => 'field_first_message', 'placeholder_key' => 'ph_first_message', 'required' => true, 'maxlength' => 300],
                ],
            ],
            [
                'slug' => 'daily-fortune',
                'icon' => 'fa-star',
                'category' => 'destiny',
                'title_key' => 'app_daily_fortune_title',
                'desc_key' => 'app_daily_fortune_desc',
                'sort_order' => 50,
                'enabled' => true,
                'badge' => null,
                'builtin_prompt' => "You are a concise daily fortune (今日運勢) advisor. Cover overall luck, career, relationships, health tips, and a lucky color/number. Keep it uplifting and non-fatalistic. Respond in the user's language.",
                'fields' => [
                    ['name' => 'birth_date', 'type' => 'text', 'label_key' => 'field_birth_date', 'placeholder_key' => 'ph_birth_date', 'required' => true, 'maxlength' => 20],
                    ['name' => 'focus', 'type' => 'select', 'label_key' => 'field_fortune_focus', 'required' => false, 'options' => [
                        ['value' => 'general', 'label_key' => 'opt_focus_general'],
                        ['value' => 'career', 'label_key' => 'opt_focus_career'],
                        ['value' => 'love', 'label_key' => 'opt_focus_love'],
                        ['value' => 'wealth', 'label_key' => 'opt_focus_wealth'],
                    ]],
                    ['name' => 'extra', 'type' => 'text', 'label_key' => 'field_fortune_extra', 'placeholder_key' => 'ph_fortune_extra', 'required' => false, 'maxlength' => 80],
                ],
            ],
        ];
    }

    /**
     * Normalize MINI_APP_SOUL_MAP entry: int | int[] → positive unique ids (map order preserved).
     *
     * @return list<int>
     */
    public static function resolveSoulIds(string $slug): array
    {
        if (!defined('MINI_APP_SOUL_MAP') || !is_array(MINI_APP_SOUL_MAP) || !isset(MINI_APP_SOUL_MAP[$slug])) {
            return [];
        }
        $raw = MINI_APP_SOUL_MAP[$slug];
        if (is_int($raw) || is_string($raw) || is_float($raw)) {
            $ids = [(int)$raw];
        } elseif (is_array($raw)) {
            $ids = array_map('intval', $raw);
        } else {
            return [];
        }
        $out = [];
        $seen = [];
        foreach ($ids as $id) {
            if ($id > 0 && !isset($seen[$id])) {
                $seen[$id] = true;
                $out[] = $id;
            }
        }
        return $out;
    }

    /** First mapped soul id, or 0 (compat). */
    public static function resolveSoulId(string $slug): int
    {
        $ids = self::resolveSoulIds($slug);
        return $ids[0] ?? 0;
    }

    /**
     * Load public non-NFT soul metadata for mapped ids (no content).
     *
     * @param list<int> $ids
     * @return list<array<string, mixed>>
     */
    public static function loadSoulMetas(PDO $pdo, array $ids): array
    {
        if ($ids === []) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "SELECT s.id, s.title, s.description, s.role, u.username
                FROM souls s
                LEFT JOIN users u ON u.id = s.user_id
                WHERE s.id IN ({$placeholders})
                  AND s.is_public = 1
                  AND (s.is_nft = 0 OR s.is_nft IS NULL)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_values($ids));
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $byId = [];
        foreach ($rows as $row) {
            $byId[(int)$row['id']] = [
                'id' => (int)$row['id'],
                'title' => (string)($row['title'] ?? ''),
                'description' => (string)($row['description'] ?? ''),
                'role' => (string)($row['role'] ?? ''),
                'username' => (string)($row['username'] ?? ''),
            ];
        }
        // Preserve map order
        $ordered = [];
        foreach ($ids as $id) {
            if (isset($byId[$id])) {
                $ordered[] = $byId[$id];
            }
        }
        return $ordered;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function listPublic(?string $category = null, ?string $q = null): array
    {
        $out = [];
        foreach (self::allRaw() as $app) {
            if (empty($app['enabled'])) {
                continue;
            }
            if ($category !== null && $category !== '' && ($app['category'] ?? '') !== $category) {
                continue;
            }
            $title = function_exists('__') ? __($app['title_key']) : $app['title_key'];
            $desc = function_exists('__') ? __($app['desc_key']) : $app['desc_key'];
            if ($q !== null && $q !== '') {
                $hay = mb_strtolower($title . ' ' . $desc . ' ' . $app['slug'], 'UTF-8');
                if (mb_strpos($hay, mb_strtolower($q, 'UTF-8')) === false) {
                    continue;
                }
            }
            $soulIds = self::resolveSoulIds($app['slug']);
            // Hide apps until MINI_APP_SOUL_MAP has at least one positive soul id
            if ($soulIds === []) {
                continue;
            }
            $out[] = [
                'slug' => $app['slug'],
                'icon' => $app['icon'],
                'category' => $app['category'],
                'title' => $title,
                'description' => $desc,
                'badge' => $app['badge'] ?? null,
                'field_count' => count($app['fields'] ?? []),
                'soul_count' => count($soulIds),
                'soul_configured' => true,
                'sort_order' => (int)($app['sort_order'] ?? 0),
            ];
        }
        usort($out, fn($a, $b) => $a['sort_order'] <=> $b['sort_order']);
        return $out;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function getBySlug(string $slug): ?array
    {
        foreach (self::allRaw() as $app) {
            if (($app['slug'] ?? '') === $slug && !empty($app['enabled'])) {
                return $app;
            }
        }
        return null;
    }

    /**
     * Public detail with localized labels + selectable soul intros (no soul content).
     *
     * @return array<string, mixed>|null
     */
    public static function getPublicDetail(string $slug, ?PDO $pdo = null): ?array
    {
        $app = self::getBySlug($slug);
        if (!$app) {
            return null;
        }
        $mappedIds = self::resolveSoulIds($slug);
        if ($mappedIds === []) {
            return null;
        }

        $souls = [];
        if ($pdo instanceof PDO) {
            $souls = self::loadSoulMetas($pdo, $mappedIds);
            if ($souls === []) {
                return null; // none of the mapped ids are public/usable
            }
        }

        $fields = [];
        foreach ($app['fields'] as $f) {
            $field = [
                'name' => $f['name'],
                'type' => $f['type'],
                'label' => function_exists('__') ? __($f['label_key']) : $f['label_key'],
                'required' => !empty($f['required']),
            ];
            if (!empty($f['placeholder_key'])) {
                $field['placeholder'] = function_exists('__') ? __($f['placeholder_key']) : $f['placeholder_key'];
            }
            if (isset($f['maxlength'])) {
                $field['maxlength'] = (int)$f['maxlength'];
            }
            if (!empty($f['options']) && is_array($f['options'])) {
                $field['options'] = array_map(function ($opt) {
                    return [
                        'value' => $opt['value'],
                        'label' => function_exists('__') ? __($opt['label_key']) : $opt['label_key'],
                    ];
                }, $f['options']);
            }
            $fields[] = $field;
        }

        return [
            'slug' => $app['slug'],
            'icon' => $app['icon'],
            'category' => $app['category'],
            'title' => function_exists('__') ? __($app['title_key']) : $app['title_key'],
            'description' => function_exists('__') ? __($app['desc_key']) : $app['desc_key'],
            'badge' => $app['badge'] ?? null,
            'fields' => $fields,
            'souls' => $souls,
            'soul_count' => count($souls),
            'soul_configured' => count($souls) > 0,
        ];
    }

    /** Whether soul_id is allowed for this app slug (map membership only). */
    public static function isSoulAllowed(string $slug, int $soulId): bool
    {
        if ($soulId <= 0) {
            return false;
        }
        return in_array($soulId, self::resolveSoulIds($slug), true);
    }

    /**
     * Validate submitted fields against schema. Returns [ok, errorMessage, sanitizedValues].
     *
     * @param array<string, mixed> $input
     * @return array{0:bool,1:?string,2:array<string,string>}
     */
    public static function validateFields(array $app, array $input): array
    {
        $sanitized = [];
        foreach ($app['fields'] as $f) {
            $name = $f['name'];
            $raw = $input[$name] ?? '';
            if (is_array($raw)) {
                return [false, function_exists('__') ? __('Invalid app field value') : 'Invalid app field value', []];
            }
            $val = trim((string)$raw);
            if (!empty($f['required']) && $val === '') {
                $label = function_exists('__') ? __($f['label_key']) : $f['label_key'];
                $msg = function_exists('__')
                    ? __('Missing required app field', ['field' => $label])
                    : "Missing required field: {$label}";
                return [false, $msg, []];
            }
            if ($val === '') {
                continue;
            }
            $max = isset($f['maxlength']) ? (int)$f['maxlength'] : 500;
            if (mb_strlen($val, 'UTF-8') > $max) {
                $label = function_exists('__') ? __($f['label_key']) : $f['label_key'];
                $msg = function_exists('__')
                    ? __('App field too long', ['field' => $label, 'max' => $max])
                    : "Field too long: {$label}";
                return [false, $msg, []];
            }
            if (($f['type'] ?? '') === 'select' && !empty($f['options'])) {
                $allowed = array_column($f['options'], 'value');
                if (!in_array($val, $allowed, true)) {
                    return [false, function_exists('__') ? __('Invalid app field value') : 'Invalid app field value', []];
                }
            }
            $sanitized[$name] = $val;
        }
        return [true, null, $sanitized];
    }

    /**
     * Build labeled user message from sanitized fields.
     *
     * @param array<string, string> $values
     */
    public static function formatUserMessage(array $app, array $values): string
    {
        // Compact labeled lines so Free-tier max_input (often 100 chars) can still run short forms.
        $lines = [];
        foreach ($app['fields'] as $f) {
            $name = $f['name'];
            if (!array_key_exists($name, $values)) {
                continue;
            }
            $label = function_exists('__') ? __($f['label_key']) : $f['label_key'];
            $display = $values[$name];
            if (($f['type'] ?? '') === 'select' && !empty($f['options'])) {
                foreach ($f['options'] as $opt) {
                    if ($opt['value'] === $display) {
                        $display = function_exists('__') ? __($opt['label_key']) : $opt['label_key'];
                        break;
                    }
                }
            }
            $lines[] = "{$label}: {$display}";
        }
        return implode("\n", $lines);
    }

    public static function buildSystemPrompt(array $app, ?array $soul, array $tierConfig): string
    {
        $systemPrompt = '';
        if ($soul) {
            if (($soul['file_type'] ?? '') === 'full_soul_folder') {
                $systemPrompt .= "Please adopt the following modular AI persona:\n\n";
                $files = json_decode(str_replace("\\'", "'", $soul['content'] ?? ''), true);
                if (is_array($files)) {
                    foreach ($files as $filename => $fileContent) {
                        if (strpos((string)$filename, 'ERROR.md') !== false) {
                            continue;
                        }
                        $systemPrompt .= "=== MODULE: {$filename} ===\n"
                            . (is_string($fileContent) ? $fileContent : json_encode($fileContent, JSON_UNESCAPED_UNICODE))
                            . "\n\n";
                    }
                }
            } else {
                $systemPrompt = (string)($soul['content'] ?? '');
            }
        } elseif (!empty($app['builtin_prompt'])) {
            $systemPrompt = (string)$app['builtin_prompt'];
        }

        // Align length pressure with tier max_tokens (e.g. free 500) — same idea as chat word cap
        $maxWords = max(40, floor(($tierConfig['max_tokens'] ?? 500) * 0.55));
        $systemPrompt .= "\n\n[MINI APP MODE] Structured form answer. Be concise and fit within ~{$maxWords} words. Prefer a short table over long prose. Stop cleanly when the answer is complete.";
        return $systemPrompt;
    }
}
