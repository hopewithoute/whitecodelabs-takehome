import js from '@eslint/js';
import pluginVue from 'eslint-plugin-vue';

export default [
  // ── Global ignores ──────────────────────────────────────────────
  {
    ignores: [
      'vendor/**',
      'node_modules/**',
      'public/**',
      'storage/**',
      'bootstrap/ssr/**',
      'tailwind.config.js',
      '*.config.*',
      'resources/js/components/ui/**',  // shadcn-vue generated library
    ],
  },

  // ── Base JS recommended rules ───────────────────────────────────
  js.configs.recommended,

  // ── Vue 3 recommended rules (flat config) ───────────────────────
  ...pluginVue.configs['flat/recommended'],

  // ── Project-wide overrides & Vue settings ───────────────────────
  {
    files: ['resources/**/*.{js,vue}', 'vite.config.js'],
    languageOptions: {
      ecmaVersion: 'latest',
      sourceType: 'module',
      globals: {
        // Browser
        window: 'readonly',
        document: 'readonly',
        navigator: 'readonly',
        console: 'readonly',
        setTimeout: 'readonly',
        setInterval: 'readonly',
        clearTimeout: 'readonly',
        clearInterval: 'readonly',
        fetch: 'readonly',
        URL: 'readonly',
        URLSearchParams: 'readonly',
        FormData: 'readonly',
        Blob: 'readonly',
        FileReader: 'readonly',
        HTMLElement: 'readonly',
        HTMLInputElement: 'readonly',
        HTMLSelectElement: 'readonly',
        HTMLTextAreaElement: 'readonly',
        HTMLButtonElement: 'readonly',
        HTMLFormElement: 'readonly',
        HTMLDivElement: 'readonly',
        HTMLAnchorElement: 'readonly',
        HTMLImageElement: 'readonly',
        HTMLSpanElement: 'readonly',
        SVGElement: 'readonly',
        Element: 'readonly',
        Node: 'readonly',
        Event: 'readonly',
        MouseEvent: 'readonly',
        KeyboardEvent: 'readonly',
        FocusEvent: 'readonly',
        InputEvent: 'readonly',
        PointerEvent: 'readonly',
        WheelEvent: 'readonly',
        DragEvent: 'readonly',
        ClipboardEvent: 'readonly',
        CustomEvent: 'readonly',
        requestAnimationFrame: 'readonly',
        cancelAnimationFrame: 'readonly',
        IntersectionObserver: 'readonly',
        MutationObserver: 'readonly',
        ResizeObserver: 'readonly',
        matchMedia: 'readonly',
        getComputedStyle: 'readonly',
        DOMParser: 'readonly',
        Image: 'readonly',
        crypto: 'readonly',
        localStorage: 'readonly',
        sessionStorage: 'readonly',
        location: 'readonly',
        history: 'readonly',
        alert: 'readonly',
        confirm: 'readonly',
        prompt: 'readonly',
        performance: 'readonly',

        // Node / Vite
        process: 'readonly',
        require: 'readonly',
        import: 'readonly',
        module: 'readonly',
        __dirname: 'readonly',
        __filename: 'readonly',
        defineProps: 'readonly',
        defineEmits: 'readonly',
        defineExpose: 'readonly',
        defineModel: 'readonly',
        defineOptions: 'readonly',
        defineSlots: 'readonly',
        withDefaults: 'readonly',
      },
    },
  },

  // ── Vue SFC specific config ─────────────────────────────────────
  {
    files: ['resources/**/*.vue'],
    languageOptions: {
      parserOptions: {
        ecmaVersion: 'latest',
        sourceType: 'module',
      },
    },
    rules: {
      // ─── Vue 3 Best Practices ─────────────────────────────────
      'vue/multi-word-component-names': 'off', // Allow single-word names (common in Vue 3)
      'vue/define-macros-order': ['warn', {
        order: ['defineProps', 'defineEmits', 'defineSlots'],
      }],
      'vue/block-order': ['warn', {
        order: ['script', 'template', 'style'],
      }],
      'vue/define-props-declaration': ['warn', 'type-based'],
      'vue/define-emits-declaration': ['warn', 'type-based'],
      'vue/no-unused-refs': 'warn',
      'vue/no-useless-v-bind': 'warn',
      'vue/prefer-true-attribute-shorthand': 'warn',
      'vue/component-api-style': ['warn', ['script-setup', 'composition']],
      'vue/eqeqeq': ['error', 'always', { null: 'ignore' }],
      'vue/no-v-html': 'warn',
      'vue/require-explicit-emits': 'warn',
      'vue/no-mutating-props': 'error',
      'vue/no-v-text': 'warn',
      'vue/no-parsing-error': 'off',  // false positives with complex template expressions

      // ─── Template best practices ──────────────────────────────
      'vue/html-indent': ['warn', 4],
      'vue/max-attributes-per-line': ['warn', {
        singleline: { max: 3 },
        multiline: { max: 1 },
      }],
      'vue/singleline-html-element-content-newline': 'off',
      'vue/html-self-closing': ['warn', {
        html: { void: 'always', normal: 'never', component: 'always' },
        svg: 'always',
        math: 'always',
      }],
      'vue/attributes-order': ['warn', {
        order: [
          'DEFINITION',
          'LIST_RENDERING',
          'CONDITIONALS',
          'RENDER_MODIFIERS',
          'GLOBAL',
          'UNIQUE',
          'SLOT',
          'TWO_WAY_BINDING',
          'OTHER_DIRECTIVES',
          'OTHER_ATTR',
          'EVENTS',
          'CONTENT',
        ],
        alphabetical: false,
      }],
      'vue/order-in-components': 'warn',
      'vue/no-unused-components': 'warn',
      'vue/no-unused-vars': 'warn',
      'vue/no-template-shadow': 'warn',
      'vue/this-in-template': 'error',
      'vue/padding-line-between-blocks': 'warn',
      'vue/prefer-separate-static-class': 'warn',

      // ─── Script rules (inherited from JS but refined) ─────────
      'no-unused-vars': ['warn', {
        argsIgnorePattern: '^_',
        varsIgnorePattern: '^_',
      }],
      'no-console': ['warn', { allow: ['warn', 'error'] }],
      'prefer-const': 'error',
      'no-var': 'error',
      'eqeqeq': ['error', 'always', { null: 'ignore' }],
      'no-duplicate-imports': 'off',
      'import/no-duplicates': 'off',
      'arrow-body-style': ['warn', 'as-needed'],
      'object-shorthand': ['warn', 'always'],
      'prefer-template': 'warn',
    },
  },

  // ── JavaScript files config ─────────────────────────────────────
  {
    files: ['resources/**/*.js'],
    rules: {
      'no-unused-vars': ['warn', {
        argsIgnorePattern: '^_',
        varsIgnorePattern: '^_',
      }],
      'no-console': ['warn', { allow: ['warn', 'error'] }],
      'prefer-const': 'error',
      'no-var': 'error',
      'eqeqeq': ['error', 'always', { null: 'ignore' }],
      'no-duplicate-imports': 'off',
      'import/no-duplicates': 'off',
      'arrow-body-style': ['warn', 'as-needed'],
      'object-shorthand': ['warn', 'always'],
      'prefer-template': 'warn',
    },
  },
];
