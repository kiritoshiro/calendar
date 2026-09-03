# GitHub updates

This fork checks the private `kiritoshiro/calendar` repository directly and
appears in the normal WordPress **Plugins** and **Dashboard → Updates** screens.

Add a fine-grained GitHub token with read-only **Contents** access to this
repository in `wp-config.php`:

```php
define('ADVENTISTAI_CALENDAR_GITHUB_TOKEN', 'github_pat_REPLACE_ME');
```

`ADVENTISTAI_GITHUB_TOKEN` is also accepted as a shared fallback. Keep the
token above WordPress's “stop editing” line, do not commit it, and give it no
write permissions.

The updater follows the repository's current default branch. To publish an
update, increment both the plugin header `Version` and `MEC_VERSION` in
`modern-events-calendar-lite.php`, then push the change. WordPress's **Check
Again** action clears the updater cache and detects the new version.

The repository contains the plugin in the `modern-events-calendar-lite`
subdirectory. The updater validates that directory before installation so an
unexpected GitHub archive layout cannot overwrite another plugin directory.
