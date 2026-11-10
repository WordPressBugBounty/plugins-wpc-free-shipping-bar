/**
 * Free shipping bar for the WooCommerce Cart/Checkout blocks.
 *
 * Rendered through the order summary "discounts meta" Slot/Fill, so it works on both Cart and Checkout blocks.
 * On the Checkout block, if that slot is missing from the page, it falls back to the "order meta" slot
 * (next to the Place Order button).
 * Data comes from the Store API cart extension (namespace "wpcfb") and is refreshed on every cart update.
 */
(function (wp, wc) {
	'use strict';

	if (!wp || !wc || !wc.blocksCheckout || !wp.plugins || !wp.element || !wp.data) {
		return;
	}

	var el = wp.element.createElement;
	var RawHTML = wp.element.RawHTML;
	var Fragment = wp.element.Fragment;
	var useState = wp.element.useState;
	var useEffect = wp.element.useEffect;
	var useRef = wp.element.useRef;
	var useSelect = wp.data.useSelect;
	var registerPlugin = wp.plugins.registerPlugin;

	var DiscountsMeta = wc.blocksCheckout.ExperimentalDiscountsMeta || wc.blocksCheckout.DiscountsMeta;
	var OrderMeta = wc.blocksCheckout.ExperimentalOrderMeta || wc.blocksCheckout.OrderMeta;
	var NAMESPACE = 'wpcfb';

	if (!DiscountsMeta) {
		return;
	}

	// Tracks whether the primary (discounts meta) fill is mounted, so the fallback can be skipped
	var primaryMounted = false;
	var listeners = [];

	function setPrimaryMounted(value) {
		primaryMounted = value;
		listeners.forEach(function (listener) {
			listener();
		});
	}

	/**
	 * Free shipping bar.
	 */
	function ShippingBar(props) {
		var data = useSelect(function (select) {
			var cart = select('wc/store/cart').getCartData();

			return (cart && cart.extensions && cart.extensions[NAMESPACE]) || null;
		}, []);

		var barRef = useRef(null);
		var progressRef = useRef(null);

		// Let the plugin know when the primary placement is rendered
		useEffect(function () {
			if (props.placement !== 'primary') {
				return undefined;
			}

			setPrimaryMounted(true);

			return function () {
				setPrimaryMounted(false);
			};
		}, []);

		// Apply the dynamic colors and width through the CSSOM instead of inline markup
		useEffect(function () {
			if (!data || data.state !== 'progress') {
				return;
			}

			if (barRef.current) {
				barRef.current.style.backgroundColor = data.bar_color;
			}

			if (progressRef.current) {
				progressRef.current.style.backgroundColor = data.progress_color;
				progressRef.current.style.width = data.percent + '%';
			}
		}, [data && data.state, data && data.bar_color, data && data.progress_color, data && data.percent]);

		if (!data || (data.state !== 'progress' && data.state !== 'qualified')) {
			return null;
		}

		// Respect the "Show on cart page" / "Show on checkout page" settings
		var isCart = !!document.querySelector('.wp-block-woocommerce-cart');

		if (isCart ? !data.show_cart : !data.show_checkout) {
			return null;
		}

		if (data.state === 'qualified') {
			return el('div', {className: data.wrap_class},
				el(RawHTML, {className: 'wpcfb-message'}, data.message_html)
			);
		}

		return el('div', {className: data.wrap_class},
			el(RawHTML, {className: 'wpcfb-title'}, data.title_html),
			el('div', {className: 'wpcfb-progress-bar', ref: barRef},
				el('span', {className: 'wpcfb-progress-amount', ref: progressRef})
			),
			el(RawHTML, {className: 'wpcfb-message'}, data.message_html)
		);
	}

	/**
	 * Plugin root: always fills the discounts meta slot, and falls back to the order meta slot on the Checkout block
	 * when the discounts meta slot is not available.
	 */
	function ShippingBarPlugin() {
		var fallbackState = useState(false);
		var fallback = fallbackState[0];
		var setFallback = fallbackState[1];

		useEffect(function () {
			if (!OrderMeta || !document.querySelector('.wp-block-woocommerce-checkout')) {
				return undefined;
			}

			var timer = null;

			var update = function () {
				window.clearTimeout(timer);

				if (primaryMounted) {
					setFallback(false);
				} else {
					timer = window.setTimeout(function () {
						setFallback(!primaryMounted);
					}, 1500);
				}
			};

			listeners.push(update);
			update();

			return function () {
				window.clearTimeout(timer);
				listeners = listeners.filter(function (listener) {
					return listener !== update;
				});
			};
		}, []);

		return el(Fragment, null,
			el(DiscountsMeta, null, el(ShippingBar, {placement: 'primary'})),
			fallback ? el(OrderMeta, null, el(ShippingBar, {placement: 'fallback'})) : null
		);
	}

	registerPlugin('wpcfb-shipping-bar', {
		scope: 'woocommerce-checkout',
		render: ShippingBarPlugin
	});
})(window.wp, window.wc);

