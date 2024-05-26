module.exports = {
  env: {
    browser: true,
    es2021: true,
  },
  root: true,
  globals: {
    ProcessMaker: true,
  },
  extends: [
    'airbnb-base',
    'plugin:vue/recommended',
  ],
  parserOptions: {
    ecmaVersion: 'latest',
    sourceType: 'module',
  },
  plugins: [
    'vue',
  ],
  rules: {
    'no-new': 0,
    'import/no-extraneous-dependencies': 1,
  },
};
