# Release Note Feature Documentation

## Overview

This feature fetches and displays release notes from GitHub repositories (frontend & backend) using GitHub's REST API.

## Table of Contents

1. [Overview](#overview)
2. [Environment Variables](#environment-variables)
3. [Setup Instructions](#setup-instructions)
4. [API Endpoints](#api-endpoints)
5. [GitHub API Usage](#github-api-usage)

## Environment Variables

### Github Authentication

- **`GITHUB_TOKEN`**: Personal Access Token for Github API authentication
  - **Purpose**: Increase API rate limits from 60 request/hour to 5000 requests/hour
  - **Required**: Optional but recommended for production

### SSL Configuration

- **`GITHUB_VERIFY_SSL`**: SSL certificate verification toggle
  - **Purpose**: Controls SSL verification for API requests
  - **Default**: `false`
  - **Values**: `true` (production) | `false` (development)

### API Settings

- **`GITHUB_API_URL`**: Github API base URL
  - **Purpose**: API endpoint for GitHub requests
  - **Default**: `https://api.github.com`
  
- **`GITHUB_API_VERSION`**: API version header, require when send request to github's api.
  - **Purpose**: Specifies GitHub API version
  - **Default**: `2022-11-28` (Newest version of github)
  - **Reference**: `https://docs.github.com/en/rest/about-the-rest-api/api-versions?apiVersion=2022-11-28`
  
- **`GITHUB_API_TIMEOUT`**: Request timeout in seconds
  - **Purpose**: Prevents hanging requests, can change timeout value.
  - **Default**: `30`

### Repository Configuration

- **`GITHUB_FE_OWNER`**: Frontend repository owner
  - **Purpose**: GitHub username/organization for frontend repo
  - **Default**: `ncc-erp`
  
- **`GITHUB_FE_REPO`**: Frontend repository name
  - **Purpose**: Frontend repository name
  - **Default**: `ncc-erp-ams-fe`
  
- **`GITHUB_BE_OWNER`**: Backend repository owner
  - **Purpose**: GitHub username/organization for backend repo
  - **Default**: `ncc-erp`
  
- **`GITHUB_BE_REPO`**: Backend repository name
  - **Purpose**: Backend repository name
  - **Default**: `ncc-erp-ams`

## Setup Instructions

### Generate GitHub Personal Access Token

#### Classic Personal Access Token

1. Go to GitHub → Settings → Developer settings → Personal access tokens → Tokens (classic).
2. Click "Generate new token".
3. Add a Note to describe the token usage.
4. Select Expiration date (or No expiration if you want it permanent — not recommended for security)
5. Select Scopes:
   - public_repo → Access to public repositories only.
   - repo → Access to both public and private repositories you have permission to.
   - And select other scopes.
6. Click Generate token.
7. Copy and store the token securely — it will only be shown once.

#### Fine-grained Personal Access Token

1. Go to Github → Settings → Developer settings → Personal access tokens → Fine-grained tokens.
2. Click "Generate new token".
3. Enter a Token name and optional Description.
4. Set Expiration date (mandatory).
5. Resource owner: Choose your account or an organization you belong to.
6. Repository access:
   - Only select repositories → Choose specific repositories (up to 50).
   - All repositories → For all current and future repositories you have access to under the selected owner.
7. Set Permissions (Example: read/write/admin for each resource type like Code, Issues, Actions, Packages, etc.).
8. Click Generate token.
9. Copy and store the token securely — it will only be shown once.

### Configure Environment Variables

- Add the following to your `.env` file:

```bash
# GitHub API Configuration
GITHUB_TOKEN=your_github_token_here
GITHUB_VERIFY_SSL=false

# GitHub API Settings
GITHUB_API_URL=https://api.github.com
GITHUB_API_VERSION=2022-11-28
GITHUB_API_TIMEOUT=30

# Repository Information
GITHUB_FE_OWNER=your_organization
GITHUB_FE_REPO=your_frontend_repo
GITHUB_BE_OWNER=your_organization
GITHUB_BE_REPO=your_backend_repo
```

- Replace GITHUB_TOKEN with your access token.
- Replace GITHUB_VERIFY_SSL to true if environment is production or in localhost but server has "cert.pem".
- Update repository information if needed.

### Clear Configuration Cache

```bash
php artisan config:cache
```

## API Endpoints

### Get Release Notes

**Endpoint**: `GET /api/releases-notes`

**Parameters**:

- `per_page` (optional): Number of releases per repository (default: 30)

**Response**: JSON array of release objects sorted by publish date (newest first)

**Example Request**:

```bash
GET /api/releases-notes?per_page=10
```

**Example Response**:

```json
[
  {
    "id": 12345,
    "name": "v1.2.0",
    "tag_name": "v1.2.0",
    "published_at": "2023-10-01T10:00:00Z",
    "body": "Release notes content...",
    "html_url": "https://github.com/owner/repo/releases/tag/v1.2.0"
  }
]
```

## GitHub API Usage

### Endpoints Used

- **`GET /repos/{owner}/{repo}/releases`**: Fetches release notes from specified repository

- To fetch release notes from GitHub api, we should fetch from:
  `https://api.github.com/repos/ncc-erp/ncc-erp-ams-fe/releases`
  `https://api.github.com/repos/ncc-erp/ncc-erp-ams/releases`

- We also can use parameter 'per_page' to get pagination from this api, for example:
  `https://api.github.com/repos/ncc-erp/ncc-erp-ams-fe/releases?per_page=10`

### Rate Limits

- **Without token**: 60 requests/hour per IP
- **With token**: 5,000 requests/hour per token

### Caching Strategy

- **Cache Duration**: 1 hour
- **Cache Keys**: 
  - Individual repos: `github_releases_{owner}_{repo}_{perPage}`
  - All releases: `github_all_releases_{perPage}`
