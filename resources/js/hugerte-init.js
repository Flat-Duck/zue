// The editor used on the clinic screens.
//
// HugeRTE loads its skin, icons and plugins as separate modules. Importing them
// here bundles them; anything not imported is fetched from a base URL at runtime,
// which is why `skin_url` and `content_css` are set to `default` at every init site.
//
// Only the plugins the clinic screens actually configure are imported. The package
// ships thirty; eleven of them — emoticons and its emoji data, codesample and its
// syntax highlighter, template, accordion, autoresize, autosave, directionality,
// nonbreaking, pagebreak, quickbars, save, visualchars — were bundled without any
// screen asking for them.

// The HugeRTE global. This must come before the other imports.
import 'hugerte';

// The DOM model, icons, theme and skin.
import 'hugerte/models/dom';
import 'hugerte/icons/default';
import 'hugerte/themes/silver';
import 'hugerte/skins/ui/oxide/skin.js';
import 'hugerte/skins/ui/oxide/content.js';
import 'hugerte/skins/content/default/content.js';

// The plugins named by the clinic screens, and nothing else.
import 'hugerte/plugins/advlist';
import 'hugerte/plugins/anchor';
import 'hugerte/plugins/autolink';
import 'hugerte/plugins/charmap';
import 'hugerte/plugins/code';
import 'hugerte/plugins/fullscreen';
import 'hugerte/plugins/help';
import 'hugerte/plugins/image';
import 'hugerte/plugins/insertdatetime';
import 'hugerte/plugins/link';
import 'hugerte/plugins/lists';
import 'hugerte/plugins/media';
import 'hugerte/plugins/preview';
import 'hugerte/plugins/searchreplace';
import 'hugerte/plugins/table';
import 'hugerte/plugins/visualblocks';
import 'hugerte/plugins/wordcount';

// The help plugin needs its keyboard-navigation strings, even in English.
import 'hugerte/plugins/help/js/i18n/keynav/en.js';
