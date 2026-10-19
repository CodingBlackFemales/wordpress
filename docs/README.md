# Docs

The CBF Academy content style guide, the slides migration playbook and the `cbf-academy-style` and `cbf-slides-migration` agent skills have moved to [cbfacademy/content-management](https://github.com/cbfacademy/content-management), so content editors can use them without access to this repository. The guides are published there as a private GitHub Pages site.

To use the skills in Claude Code:

```text
/plugin marketplace add cbfacademy/content-management
/plugin install cbf-academy-content@cbf-academy
```

For other agents, clone that repository and run `scripts/install-skills.sh`.

`diagrams/` holds the SVG sources for diagrams in migrated lessons. New diagram sources are kept with each lesson's source material, as the style guide's "Diagrams" section describes.
