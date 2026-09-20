  <script>
    function dashboard() {
      return {
        page: @json(request()->query('__unused_keys') ? 'unused' : (request()->segment(2) ?: 'overview')),
        ar: {{ $_isRtl ? 'true' : 'false' }},
        expanded: true,
        toasts: [],
        restoreOpen: false,
        restoreTarget: '',
        lockOpen: false,
        lockLang: @json($cfgLangsJs[0]['code'] ?? 'ar'),
        lockKey: '',
        lockReason: '',
        addOpen: false,
        addQuery: '',
        addingLang: '',
        activeProvider: @json($cfgDriver),
        providerDisplay: @json($_providerDisplay),
        configuredLangs: @json($cfgLangsJs),
        sourceLang: @json($cfgSource),
        languageCatalog: @json($_languageCatalog),
        t: @json($_trans),

        async init() {
          try {
            this.expanded = localStorage.getItem('lat-sidebar-collapsed') !== '1';
          } catch (e) {}
          this.startBackgroundPoller();
        },

        _tBak() {
          const en = {
            overview: 'Overview', languages: 'Languages', locked: 'Locked Keys', history: 'History', unused: 'Unused Keys', backups: 'Backups', settings: 'Settings', collapse: 'Collapse',
            quickActions: 'Quick actions', scan: 'Scan', translate: 'Translate', dryRun: 'Dry run', activeProvider: 'Active provider', lastSync: 'Last sync',
            coverageByLang: 'Coverage by language', addLanguage: 'Add language', addLanguageHint: 'Select a language to add', searchLanguage: 'Search languages...',
            language: 'Language', key: 'Key', value: 'Value', lockedBy: 'Locked by', reason: 'Reason', unlock: 'Unlock', lockKeyTitle: 'Lock a Key',
            reasonOptional: 'Reason (optional)', reasonPlaceholder: 'e.g. Client preferred term', lockKeyConfirm: 'Lock Key', cancel: 'Cancel',
            restoreTitle: 'Restore Backup', restoreWarn: 'This will replace all current language files.', restoreConfirm: 'Yes, restore', backupSettings: 'Backup settings',
            keepLast: 'Keep last', keepLastHint: 'Older backups are deleted automatically', autoBackup: 'Auto-backup', autoBackupHint: 'Before every translation run',
            createBackup: 'Create backup', download: 'Download', restore: 'Restore', emptyBackupsT: 'No backups yet', emptyBackupsB: 'A backup is created automatically before every translation run.',
            save: 'Save changes', reset: 'Reset to defaults', saving: 'Saving...', files: 'files', keys: 'keys'
          };
          const ar = {
            overview: 'نظرة عامة', languages: 'اللغات', locked: 'المفاتيح المقفلة', history: 'السجل', unused: 'المفاتيح غير المستخدمة', backups: 'النسخ الاحتياطية', settings: 'الإعدادات', collapse: 'طيّ',
            quickActions: 'إجراءات سريعة', scan: 'مسح', translate: 'ترجمة', dryRun: 'تجريبي', activeProvider: 'المزوّد النشط', lastSync: 'آخر مزامنة',
            coverageByLang: 'التغطية حسب اللغة', addLanguage: 'إضافة لغة', addLanguageHint: 'اختر لغة لإضافتها', searchLanguage: 'ابحث عن لغة...',
            language: 'اللغة', key: 'المفتاح', value: 'القيمة', lockedBy: 'قفل بواسطة', reason: 'السبب', unlock: 'فتح', lockKeyTitle: 'قفل مفتاح',
            reasonOptional: 'السبب (اختياري)', reasonPlaceholder: 'مثال: مصطلح العميل', lockKeyConfirm: 'تأكيد القفل', cancel: 'إلغاء',
            restoreTitle: 'استعادة نسخة', restoreWarn: 'سيؤدي هذا إلى استبدال جميع ملفات اللغة.', restoreConfirm: 'تأكيد الاستعادة', backupSettings: 'إعدادات النسخ الاحتياطي',
            keepLast: 'الاحتفاظ بآخر', keepLastHint: 'يتم حذف النسخ القديمة تلقائياً', autoBackup: 'نسخ تلقائي', autoBackupHint: 'قبل كل عملية ترجمة',
            createBackup: 'إنشاء نسخة', download: 'تحميل', restore: 'استعادة', emptyBackupsT: 'لا توجد نسخ احتياطية', emptyBackupsB: 'تُنشأ نسخة تلقائياً قبل كل ترجمة.',
            save: 'حفظ التغييرات', reset: 'إعادة الضبط', saving: 'جاري الحفظ...', files: 'ملفات', keys: 'مفتاح'
          };
          return this.ar ? ar : en;
        },

        get pageTitle() {
          const e = { overview: 'Overview', languages: 'Languages', locked: 'Locked Keys', history: 'History', unused: 'Unused Keys', backups: 'Backups', settings: 'Settings' };
          const a = { overview: 'نظرة عامة', languages: 'اللغات', locked: 'المفاتيح المقفلة', history: 'السجل', unused: 'المفاتيح غير المستخدمة', backups: 'النسخ الاحتياطية', settings: 'الإعدادات' };
          return (this.ar ? a : e)[this.page] || '';
        },

        get pageSub() {
          const e = {
            overview: 'Translation state of your application',
            languages: 'Locales configured in config/ai-translator.php',
            locked: 'Keys protected from being overwritten',
            history: 'Every sync run, with cost and diff',
            unused: 'Translation keys that are not currently referenced',
            backups: 'Snapshots of your language files',
            settings: 'Provider, models and behaviour'
          };
          const a = {
            overview: 'حالة الترجمة في تطبيقك',
            languages: 'اللغات المُهيّأة',
            locked: 'المفاتيح المحمية',
            history: 'سجلات المزامنة',
            unused: 'مفاتيح الترجمة غير المستخدمة',
            backups: 'لقطات ملفات اللغة',
            settings: 'المزوّد والنماذج'
          };
          return (this.ar ? a : e)[this.page] || '';
        },

        get providerLabel() {
          return this.providerDisplay || this.activeProvider;
        },

        get filteredAddOptions() {
          const configuredCodes = this.configuredLangs.map(l => l.code);
          const available = (this.languageCatalog || []).filter(o => o.code !== this.sourceLang && !configuredCodes.includes(o.code));
          if (!this.addQuery) return available;
          const q = this.addQuery.toLowerCase();
          return available.filter(o =>
            o.code.toLowerCase().includes(q) ||
            o.name.toLowerCase().includes(q) ||
            o.native.toLowerCase().includes(q)
          );
        },

        toggleSidebar() {
          this.expanded = !this.expanded;
          try { localStorage.setItem('lat-sidebar-collapsed', this.expanded ? '0' : '1'); } catch (e) {}
        },

        go(p) {
          const urls = {
            overview: @json(route('ai-translator.overview')),
            languages: @json(route('ai-translator.languages')),
            locked: @json(route('ai-translator.locked')),
            history: @json(route('ai-translator.history')),
            backups: @json(route('ai-translator.backups')),
            settings: @json(route('ai-translator.settings')),
            unused: @json(route('ai-translator.overview', ['__unused_keys' => 1])),
          };
          if (urls[p]) window.location.href = urls[p];
        },

        async api(endpoint, body = {}) {
          const token = document.querySelector('meta[name="csrf-token"]')?.content;
          const r = await fetch(@json(url('') . '/' . trim(config('ai-translator.dashboard.path', 'ai-translator'), '/')) + '/api/' + endpoint, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json',
              ...(token ? { 'X-CSRF-TOKEN': token } : {}),
            },
            body: JSON.stringify(body),
          });
          return r.json();
        },

        async confirmRestore() {
          try {
            const r = await this.api('backups/restore', { timestamp: this.restoreTarget });
            this.restoreOpen = false;
            this.toast(r.success ? (this.ar ? 'تمت الاستعادة' : 'Backup restored') : (r.message || 'Restore failed'), '', r.success ? 'ok' : 'err');
          } catch (e) {
            this.toast(this.ar ? 'فشلت الاستعادة' : 'Restore failed', e.message, 'err');
          }
        },

        async confirmLock() {
          if (!this.lockKey.trim()) return;
          const key = this.lockKey.trim();
          try {
            const r = await this.api('lock', { lang: this.lockLang, key, reason: this.lockReason.trim() });
            this.lockOpen = false;
            this.lockKey = '';
            this.lockReason = '';
            if (r.success) {
              this.toast(this.ar ? 'تم القفل' : 'Key locked', this.lockLang + ' / ' + key);
              setTimeout(() => window.location.reload(), 900);
            } else {
              this.toast(r.message || 'Failed to lock', '', 'err');
            }
          } catch (e) {
            this.toast(this.ar ? 'فشل القفل' : 'Lock failed', e.message, 'err');
          }
        },

        async pickLanguage(opt) {
          this.addOpen = false;
          this.addingLang = opt.code;

          try {
            const r = await this.api('languages/add', { locale: opt.code });
            if (!r.success) {
              this.toast(r.message || 'Failed to add language', '', 'err');
              this.addingLang = '';
              return;
            }

            let prevCompletedAt = null;
            try {
              const pre = await fetch(@json(url('') . '/' . trim(config('ai-translator.dashboard.path', 'ai-translator'), '/')) + '/api/translate/status', { headers: { 'Accept': 'application/json' } }).then(r => r.json());
              prevCompletedAt = pre.data?.last_completed_at ?? null;
            } catch (e) {}

            fetch(@json(url('') . '/' . trim(config('ai-translator.dashboard.path', 'ai-translator'), '/')) + '/api/translate', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
              },
              body: JSON.stringify({ lang: opt.code, force: true }),
            }).catch(() => {});

            localStorage.setItem('ai_translator_pending', JSON.stringify({
              lang: opt.code,
              name: opt.name,
              started: Date.now(),
              prevCompletedAt,
            }));

            this.toast(
              this.ar ? 'جاري الترجمة في الخلفية' : opt.name + ' is being translated in the background',
              this.ar ? 'يمكنك التنقل بحرية' : 'You can navigate freely'
            );

            setTimeout(() => { window.location.href = @json(route('ai-translator.languages')); }, 800);
          } catch (e) {
            this.toast('Error: ' + e.message, '', 'err');
          }

          this.addingLang = '';
        },

        startBackgroundPoller() {
          const pending = localStorage.getItem('ai_translator_pending');
          if (!pending) return;

          try {
            const p = JSON.parse(pending);
            const name = p.name || p.lang;
            const prevCompletedAt = p.prevCompletedAt ?? null;
            const startTime = p.started;
            const self = this;

            this.toast(
              this.ar ? 'جاري الترجمة في الخلفية' : 'Translating ' + name + ' in background...',
              this.ar ? 'سيتم الإشعار عند الانتهاء' : 'You will be notified when done'
            );

            const poll = setInterval(async () => {
              try {
                const s = await fetch(@json(url('') . '/' . trim(config('ai-translator.dashboard.path', 'ai-translator'), '/')) + '/api/translate/status', { headers: { 'Accept': 'application/json' } }).then(r => r.json());
                if (!s.success) return;

                const data = s.data;
                const done = !data.running && data.last_completed_at !== null && data.last_completed_at !== prevCompletedAt;
                if (done) {
                  clearInterval(poll);
                  localStorage.removeItem('ai_translator_pending');
                  const success = data.last_result?.success ?? true;

                  if (success) {
                    self.toast(self.ar ? 'اكتملت الترجمة' : name + ' translation complete', self.ar ? 'تم تحديث الإحصائيات' : 'Stats updated');
                  } else {
                    self.toast(self.ar ? 'فشلت الترجمة' : name + ' translation failed', '', 'err');
                  }

                  if (typeof self.refreshStats === 'function') self.refreshStats();
                }

                if (Date.now() - startTime > 900000) {
                  clearInterval(poll);
                  localStorage.removeItem('ai_translator_pending');
                }
              } catch (e) {}
            }, 3000);
          } catch (e) {
            localStorage.removeItem('ai_translator_pending');
          }
        },

        toast(title, body = '', kind = 'ok') {
          const id = Date.now() + Math.random();
          this.toasts.push({ id, title, body, kind });
          setTimeout(() => {
            this.toasts = this.toasts.filter(t => t.id !== id);
          }, 3500);
        },
      };
    }

    function langSelector() {
      const allLangs = @json($_dashboardLangs);

      return {
        open: false,
        generating: false,
        generatingCode: '',
        currentCode: @json($_dashLang),
        allLangs,
        open: false,
menuStyle: '',

toggleLanguage(event) {
    this.open = !this.open;

    if (this.open) {
        this.$nextTick(() => {
            this.positionMenu(event.currentTarget);
        });
    }
},

positionMenu(button) {
    const rect = button.getBoundingClientRect();

    const menuWidth = 220;
    const gap = 8;
    const margin = 8;

    let left = rect.right - menuWidth;

    // Keep menu inside viewport
    left = Math.max(margin, left);
    left = Math.min(left, window.innerWidth - menuWidth - margin);

    this.menuStyle =
        `top:${rect.bottom + gap}px;left:${left}px;width:${menuWidth}px;`;
},

        get currentLabel() {
          const lang = this.allLangs.find(l => l.code === this.currentCode);
          return lang ? lang.native + ' (' + lang.code.toUpperCase() + ')' : 'EN';
        },

        async selectLang(lang) {
          this.open = false;
          if (lang.code === this.currentCode) return;

          if (lang.preTranslated) {
            this.setCookieAndReload(lang.code);
            return;
          }

          this.generating = true;
          this.generatingCode = lang.code;

          try {
            const r = await fetch(@json(url('ai-translator/api/dashboard-lang')), {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
              },
              body: JSON.stringify({ locale: lang.code }),
            }).then(r => r.json());

            if (r.success) {
              this.setCookieAndReload(lang.code);
            } else {
              alert('Could not generate translations: ' + (r.message || 'Unknown error'));
            }
          } catch (e) {
            alert('Network error: ' + e.message);
          }

          this.generating = false;
          this.generatingCode = '';
        },

        setCookieAndReload(code) {
          window.location.href = @json(url('ai-translator/set-lang')) + '/' + code;
        },
      };
    }

    document.addEventListener('DOMContentLoaded', function () {
      const toggle = document.querySelector('[data-theme-toggle]');
      if (!toggle) return;
      const updateLabel = () => {
        const dark = document.documentElement.classList.contains('dark');
        toggle.setAttribute('aria-label', dark ? 'Switch to light' : 'Switch to dark');
        toggle.setAttribute('title', dark ? 'Switch to light' : 'Switch to dark');
      };
      updateLabel();
      toggle.addEventListener('click', function () {
        const dark = document.documentElement.classList.toggle('dark');
        document.documentElement.style.colorScheme = dark ? 'dark' : 'light';
        try { localStorage.setItem('lat-theme', dark ? 'dark' : 'light'); } catch (e) {}
        updateLabel();
      });
    });
  </script>
