# Contributing

* [Development Setup](#setup)
* [Coding Rules](#rules)
* [Git Commit Guidelines](#commits)
* [Writing Documentation](#documentation)

## <a name="setup"></a> Development Setup

This document describes how to set up your development environment to build and test the Coding Black Females WordPress (CBFWP) network, and explains the basic mechanics of using `git`, `node`, `yarn` and `grunt`.

### Prerequisites

Before you can build CBFWP, you must install and configure the following dependencies on your machine:

* [Git](https://git-scm.com/): Instructions are available on the [Github Guide to Installing Git][git-setup].

* [Lando](https://lando.dev): Instructions are available on the [Lando Downloads page](https://lando.dev/download/).

You'll also need to sign up for a [GitHub account](https://github.com/signup/free) if you don't already have one.

### Forking CBFWP on Github

To contribute code to CBFWP, you must create [your own fork](https://help.github.com/forking) and open Pull Requests in the [main repository][github].

### Launching CBFWP locally

To run CBFWP locally, you'll need to clone your fork and use Lando to generate a local development environment:

```shell
# Clone your Github repository:
git clone https://github.com/<github username>/wordpress.git

# Go to the CBFWP directory:
cd wordpress

# Create a .env file:
cp .env.example .env

# Trust Lando CA:
./.lando/scripts/trust-ca.sh

# Start the Lando application:
lando start

# Copy the provided database:
mv <download location>/<dump name>.sql data/

# Import the database:
lando db-import data/<dump name>.sql
```

1. To access the local server, enter the following URL into your web browser:
   ```text
   http://codingblackfemales.lndo.site/
   ```
   By default, it serves the contents of the root site of the network, using the base twentytwentythree theme.

2. To access the admin area, visit this URL (request credentials from a member of the tech team):
   ```text
   http://codingblackfemales.lndo.site/wp/wp-admin/
   ```

## <a name="rules"></a> Coding Rules

To ensure consistency throughout the source code, keep these rules in mind as you are working:

* All features or bug fixes **must be tested** by one or more [specs][unit-testing].
* All public API methods **must be documented** with ngdoc, an extended version of jsdoc (we added
  support for markdown and templating via @ngdoc tag). To see how we document our APIs, please check
  out the existing source code and see the section about [writing documentation](#documentation)
* With the exceptions listed below, we follow the rules contained in
  [Google's JavaScript Style Guide][js-style-guide]:
    * **Do not use namespaces**: Instead,  wrap the entire CBFWP code base in an anonymous
      closure and export our API explicitly rather than implicitly.
    * Wrap all code at **100 characters**.
    * Instead of complex inheritance hierarchies, we **prefer simple objects**. We use prototypal
      inheritance only when absolutely necessary.
    * We **love functions and closures** and, whenever possible, prefer them over objects.
    * To write concise code that can be better minified, we **use aliases internally** that map to
      the external API. See our existing code to see what we mean.
    * We **don't go crazy with type annotations** for private internal APIs unless it's an internal
      API that is used throughout CBFWP. The best guidance is to do what makes the most sense.

### Specific topics

#### Provider configuration

When adding configuration (options) to [providers][docs.provider], we follow a special pattern.

- for each option, add a `method` that ...
  - works as a getter and returns the current value when called without argument
  - works as a setter and returns itself for chaining when called with argument
  - for boolean options, uses the naming scheme `<option>Enabled([enabled])`
- non-primitive options (e.g. objects) should be copied or the properties assigned explicitly to a
  new object so that the configuration cannot be changed during runtime.

For a boolean config example, see [`$compileProvider#debugInfoEnabled`][code.debugInfoEnabled]

For an object config example, see [`$location.html5Mode`][code.html5Mode]

#### Throwing errors

User-facing errors should be thrown with [`minErr`][code.minErr], a special error function that provides
errors ids, templated error messages, and adds a link to a detailed error description.

The `$compile:badrestrict` error is a good example for a well-defined `minErr`:
[code][code.badrestrict] and [description][docs.badrestrict].


## <a name="commits"></a> Git Commit Guidelines

We follow [strict rules](https://www.conventionalcommits.org/en/v1.0.0/) defining how our git commit messages can be formatted.  This leads to **more readable messages** that are easy to follow when looking through the **project history**.  But also, we use the git commit messages to **generate the CBFWP change log**.

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
The footer should contain any information about **Breaking Changes** and is also the place to [reference GitHub issues that this commit closes][closing-issues].

**Breaking Changes** should start with the word `BREAKING CHANGE:` with a space or two newlines. The rest of the commit message is then used for this.

A detailed explanation can be found in this [document][commit-message-format].

## <a name="documentation"></a> Writing Documentation

_TODO: Define PHP and JS documentation standards_

### General documentation with Markdown

Any text in tags can contain markdown syntax for formatting. Generally, you can use any markdown feature.

#### Headings

Only use *h2* headings and lower, as the page title is set in *h1*. Also make sure you follow the heading hierarchy. This ensures correct table of contents are created.

#### Code blocks
In line code can be specified by enclosing the code in back-ticks (\`). A block of multi-line code can be enclosed in triple back-ticks (\`\`\`) but it is formatted better if it is enclosed in &lt;pre&gt;...&lt;/pre&gt; tags and the code lines themselves are indented.
