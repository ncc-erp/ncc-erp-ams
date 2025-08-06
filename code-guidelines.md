# Backend Code Guidelines: Commit Rules

## Overview
This document outlines the commit message rules for the backend (BE) of the ERP-AMS project. Following these guidelines ensures consistency and clarity in the project's commit history.

---

## Commit Message Format
Each commit message must follow this format:

```
<type>(<scope>): <subject>
```

### **1. Type**
The type indicates the purpose of the commit. Allowed types are:
- **feat**: A new feature.
- **fix**: A bug fix.
- **refactor**: Code changes that neither fix a bug nor add a feature.
- **test**: Adding or updating tests.
- **revert**: Reverting a previous commit.
- **hotfix**: A critical fix that needs immediate attention.
- **BREAKING CHANGE**: A change that breaks backward compatibility.

### **2. Scope**
The scope specifies the ticket number of the task or feature affected by the commit. Examples:
- **123**: Refers to ticket number 123 in the project management system.
- **456**: Refers to ticket number 456 in the project management system.

Using ticket numbers as scope ensures traceability and clarity in the commit history.

### **3. Subject**
The subject is a brief description of the change. It must:
- Be written in lowercase.
- Be concise and clear.
- Avoid ending with a period.

---

## Examples
### Valid Commit Messages:
- `feat(AMS-123): add new login page`
- `fix(AMS-456): resolve issue with checkout endpoint integration`
- `refactor(AMS-789): update button styling`
- `test(AMS-321): add unit tests for asset list component`
- `hotfix(AMS-654): fix critical bug in environment variables`

### Invalid Commit Messages:
- `Added new feature` (missing type and scope)
- `fix: resolve issue` (missing scope)
- `feat(123): Add new feature.` (subject should not end with a period)

---
---

## Commitlint Configuration
The backend project uses [Commitlint](https://commitlint.js.org/) to enforce commit message rules. Below is the configuration:

```cjs
// filepath: commitlint.config.cjs
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
```

---

## Additional Notes
- Use meaningful commit messages to describe the changes clearly.
- Squash commits when merging pull requests to keep the history clean.
- For large changes, break them into smaller commits with clear messages.

For further assistance, contact the backend development team.