# GitHub updates

This fork checks the `kiritoshiro/calendar` repository directly and appears in
the normal WordPress **Plugins** and **Dashboard → Updates** screens.

While the repository is public, no configuration is required. Update checks
run unauthenticated and the plugin updates like any other.

A GitHub token is needed only if the repository is made private again, or to
raise GitHub's anonymous rate limit on a busy host. Add a fine-grained token
with read-only **Contents** access to this repository in `wp-config.php`:

```php
define('ADVENTISTAI_CALENDAR_GITHUB_TOKEN', 'github_pat_REPLACE_ME');
```

`ADVENTISTAI_GITHUB_TOKEN` is also accepted as a shared fallback. Keep the
token above WordPress's “stop editing” line, do not commit it, and give it no
write permissions. When a token is defined it is used automatically.

A failed update check shows a warning on the Plugins and Updates screens with
the reason. Nothing is shown while checks succeed.

The updater follows the repository's current default branch. To publish an
update, increment both the plugin header `Version` and `MEC_VERSION` in
`modern-events-calendar-lite.php`, then push the change. WordPress's **Check
Again** action clears the updater cache and detects the new version.

The repository contains the plugin in the `modern-events-calendar-lite`
subdirectory. The updater validates that directory before installation so an
unexpected GitHub archive layout cannot overwrite another plugin directory.
