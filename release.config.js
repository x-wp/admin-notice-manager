module.exports = {
  branches: [
    "master",
    {
      name: "develop",
      prerelease: true,
    },
    {
      name: "beta",
      prerelease: true,
    },
  ],
  plugins: [
    [
      "@semantic-release/commit-analyzer",
      {
        preset: "conventionalcommits",
        releaseRules: [
          { type: "chore", release: false },
          { type: "perf", release: "patch" },
          { type: "compat", release: "patch" },
          { type: "refactor", release: "patch" },
          { type: "style", release: "patch" },
        ],
        parserOpts: {
          noteKeywords: ["BREAKING CHANGE", "BREAKING CHANGES"],
        },
      },
    ],
    [
      "@semantic-release/release-notes-generator",
      {
        preset: "conventionalcommits",
        presetConfig: {
          types: [
            {
              type: "feat",
              section: ":sparkles: Features",
              hidden: false,
            },
            {
              type: "fix",
              section: ":bug: Bug Fixes",
              hidden: false,
            },
            {
              type: "compat",
              section: ":gear: Compatibility",
              hidden: false,
            },
            {
              type: "refactor",
              section: ":recycle: Refactor",
              hidden: false,
            },
            {
              type: "style",
              section: ":art: Code style",
              hidden: false,
            },
            {
              type: "perf",
              section: ":rocket: Performance",
              hidden: false,
            },
            {
              type: "chore",
              section: ":wrench: Maintenance",
              hidden: false,
            },
          ],
        },
      },
    ],
    [
      "@semantic-release/exec",
      {
        prepareCmd:
          "zip -r '/tmp/release.zip' ./src README.md LICENSE composer.json",
      },
    ],
    [
      "@semantic-release/github",
      {
        assets: [
          {
            path: "/tmp/release.zip",
            name: "xwp-admin-notice-manager-v${nextRelease.version}.zip",
            label: "xWP Admin Notice Manager v${nextRelease.version}",
          },
        ],
      },
    ],
  ],
};
