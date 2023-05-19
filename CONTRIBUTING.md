# Contributing

* [Development Setup](#setup)
* [Coding Rules](#rules)
* [Git Commit Guidelines](#commits)
* [Writing Documentation](#documentation)

## <a name="setup"></a> Development Setup

This document describes how to set up your development environment to build and test the Coding Black Females WordPress (CBFWP) project.

### Prerequisites

Before you can build CBFWP, you must install and configure the following dependencies on your machine:

* [Git](https://git-scm.com): Instructions are available on the [Github Guide to Installing Git](https://github.com/git-guides/install-git).

* [Lando](https://lando.dev): Instructions are available on the [downloads page](https://lando.dev/download).

* [VS Code](https://code.visualstudio.com): Instructions are available on the [downloads page](https://code.visualstudio.com/download).

  * [Dev Containers](https://marketplace.visualstudio.com/items?itemName=ms-vscode-remote.remote-containers) or [Remote Development](https://marketplace.visualstudio.com/items?itemName=ms-vscode-remote.vscode-remote-extensionpack) extension: Instructions are available on the [Developing inside a Container page](https://code.visualstudio.com/docs/devcontainers/containers).

You'll also need to sign up for a [GitHub account](https://github.com/signup/free) if you don't already have one.

### Forking CBFWP on Github

To contribute code to CBFWP, you must create [your own fork](https://help.github.com/forking) and open Pull Requests in the [main repository](https://github.com/CodingBlackFemales/wordpress).

### Launching CBFWP locally

To run CBFWP locally, you'll need to clone your fork and use Lando to generate a local development environment:

```shell
# Clone your Github repository:
git clone https://github.com/<github username>/wordpress.git

# Go to the CBFWP directory:
cd wordpress

# Trust Lando CA:
./.lando/scripts/trust-ca.sh

# Start the Lando application.
# This will take a few minutes the first time, go make yourself a cup of tea:
lando start

# Copy the provided database:
mv <download location>/<dump name>.sql data/

# Import the database:
lando db-import data/<dump name>.sql
```

1. [Attach VS Code to the container](https://code.visualstudio.com/docs/devcontainers/attach-container#_attach-to-a-docker-container) labelled `/codingblackfemales_appserver_1`. This will connect you to the Docker container running WordPress.
2. Open the Command Palette and select "Dev Containers: Open Container Configuration File". Copy the contents of `.lando/config/container.json` into the editor and save the file.
3. Restart VS Code and re-attach the container.
4. To access the local server, enter the following URL into your web browser:
   ```text
   https://codingblackfemales.lndo.site/
   ```
   By default, it serves the contents of the root site of the network, using the base twentytwentythree theme. The CBF Academy site is available at `https://academy.codingblackfemales.lndo.site/` and the CBF Job Board is available at `https://jobs.codingblackfemales.lndo.site/`

5. To access the admin area, visit this URL (request credentials from a member of the tech team):
   ```text
   https://codingblackfemales.lndo.site/wp/wp-admin/
   ```

> When VS Code first attaches to the container, it won't initially trust the repository because it's owned by a different user. To fix this, open the Source Control extension from the side bar and elect to trust the `/app/` repo.

## <a name="rules"></a> Coding Rules

To ensure consistency throughout the source code, keep these rules in mind as you are working:

_TODO: Define coding rules_

## <a name="commits"></a> Git Commit Guidelines

We follow the [Conventional Commits](https://www.conventionalcommits.org/en/v1.0.0/) specification, defining how our git commit messages can be formatted.  This leads to **more readable messages** that are easy to follow when looking through the **project history**.  But also, we use the git commit messages to **generate the CBFWP change log**.

### Commit Message Format
Each commit message consists of a **header**, a **body** and a **footer**.  The header has a special format that includes a **type**, a **scope** and a **subject**:

```
<type>(<scope>): <subject>
<BLANK LINE>
<body>
<BLANK LINE>
<footer>
```

The **header** is mandatory and the **scope** of the header is optional.

Any line of the commit message cannot be longer than 100 characters! This allows the message to be easier to read on GitHub as well as in various git tools.

### Reverting
If the commit reverts a previous commit, it should begin with `revert: `, followed by the header of the reverted commit. In the body it should say: `This reverts commit <hash>.`, where the hash is the SHA of the commit being reverted.

### Type
Must be one of the following:

* **feat**: A new feature
* **fix**: A bug fix
* **docs**: Documentation only changes
* **style**: Changes that do not affect the meaning of the code (white-space, formatting, missing
  semi-colons, etc)
* **refactor**: A code change that neither fixes a bug nor adds a feature
* **perf**: A code change that improves performance
* **test**: Adding missing or correcting existing tests
* **chore**: Changes to the build process or auxiliary tools and libraries such as documentation
  generation

### Scope
The scope could be anything specifying the context of the commit change, e.g. `academy`, `jobs`, `network`, `deps`, etc...

You can use `*` when the change affects more than a single scope.

### Subject
The subject contains a succinct description of the change:

* use the imperative, present tense: "change" not "changed" nor "changes"
* don't capitalize first letter
* no dot (.) at the end

### Body
Just as in the **subject**, use the imperative, present tense: "change" not "changed" nor "changes". The body should include the motivation for the change and contrast this with previous behavior.

### Footer
The footer should contain any information about **Breaking Changes** and is also the place to [reference GitHub issues that this commit closes](https://github.blog/2013-01-22-closing-issues-via-commit-messages/).

**Breaking Changes** should start with the word `BREAKING CHANGE:` with a space or two newlines. The rest of the commit message is then used for this.

A detailed explanation can be found in this [document](https://www.conventionalcommits.org/en/v1.0.0/#specification).

## <a name="documentation"></a> Writing Documentation

_TODO: Define PHP and JS documentation standards_

### General documentation with Markdown

Any text in tags can contain markdown syntax for formatting. Generally, you can use any markdown feature.

#### Headings

Only use *h2* headings and lower, as the page title is set in *h1*. Also make sure you follow the heading hierarchy. This ensures correct table of contents are created.

#### Code blocks
In line code can be specified by enclosing the code in back-ticks (\`). A block of multi-line code can be enclosed in triple back-ticks (\`\`\`) but it is formatted better if it is enclosed in &lt;pre&gt;...&lt;/pre&gt; tags and the code lines themselves are indented.
