module.exports = {
    extends: ["@commitlint/config-conventional"],
    rules: {
      "type-enum": [
        2,
        "always",
        [
          "feat",
          "fix",
          "refactor",
          "test",
          "revert",
          "hotfix",
          "BREAKING CHANGE",
        ],
      ],
      "type-empty": [2, "never"],
      "scope-case": [2, "always", "upper-case"],
      "scope-empty": [2, "never"],
      "subject-case": [2, "always", "lower-case"],
    },
  };
  