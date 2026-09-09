import _ from 'lodash';
import SignaturePad from 'signature_pad';
import TomSelect from 'tom-select';
import { Notyf } from 'notyf';
import axios from 'axios';

window._ = _;

/*
 * Bootstrap 5 comes from Tabler (see tabler-init.js), so jQuery, Bootstrap 4
 * and popper.js v1 are no longer loaded. The application markup uses the
 * Bootstrap 5 `data-bs-*` attributes throughout.
 */
window.TomSelect = TomSelect;
window.SignaturePad = SignaturePad;
window.Notyf = Notyf;

/**
 * Axios issues requests to the Laravel back end and sends the CSRF token
 * automatically from the XSRF cookie.
 */
window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
