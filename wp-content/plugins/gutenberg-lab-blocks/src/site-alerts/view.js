const DISMISSED_STORAGE_KEY = 'dismissed_site_messages';
const DISMISSED_SESSION_KEY = 'dismissed_site_messages_session';

function syncRootVisibility( root, alerts ) {
	root.hidden = alerts.every( ( alert ) => alert.hidden );
}

function readStorageMap( storage, key ) {
	try {
		const value = storage.getItem( key );
		const parsed = value ? JSON.parse( value ) : {};

		return parsed && typeof parsed === 'object' ? parsed : {};
	} catch ( error ) {
		return {};
	}
}

function writeStorageMap( storage, key, value ) {
	try {
		storage.setItem( key, JSON.stringify( value ) );
	} catch ( error ) {
		// Ignore storage failures so alerts still render.
	}
}

function isDismissed( token, dismissalExpiry ) {
	if ( ! token || ! dismissalExpiry ) {
		return false;
	}

	if ( dismissalExpiry === 'session' ) {
		return Boolean( readStorageMap( window.sessionStorage, DISMISSED_SESSION_KEY )[ token ] );
	}

	const storedValue = readStorageMap( window.localStorage, DISMISSED_STORAGE_KEY )[ token ];

	if ( ! storedValue ) {
		return false;
	}

	if ( dismissalExpiry === 'permanent' ) {
		return true;
	}

	const expiresAt = Number.parseInt( storedValue, 10 );

	if ( Number.isNaN( expiresAt ) || expiresAt <= Date.now() ) {
		const nextMap = readStorageMap( window.localStorage, DISMISSED_STORAGE_KEY );
		delete nextMap[ token ];
		writeStorageMap( window.localStorage, DISMISSED_STORAGE_KEY, nextMap );
		return false;
	}

	return true;
}

function getDismissalExpiryTimestamp( dismissalExpiry ) {
	switch ( dismissalExpiry ) {
		case '1day':
			return Date.now() + 24 * 60 * 60 * 1000;
		case '7days':
			return Date.now() + 7 * 24 * 60 * 60 * 1000;
		case '30days':
			return Date.now() + 30 * 24 * 60 * 60 * 1000;
		default:
			return 0;
	}
}

function persistDismissal( token, dismissalExpiry ) {
	if ( ! token || ! dismissalExpiry ) {
		return;
	}

	if ( dismissalExpiry === 'session' ) {
		const nextMap = readStorageMap( window.sessionStorage, DISMISSED_SESSION_KEY );
		nextMap[ token ] = true;
		writeStorageMap( window.sessionStorage, DISMISSED_SESSION_KEY, nextMap );
		return;
	}

	const nextMap = readStorageMap( window.localStorage, DISMISSED_STORAGE_KEY );
	nextMap[ token ] =
		dismissalExpiry === 'permanent'
			? 'permanent'
			: String( getDismissalExpiryTimestamp( dismissalExpiry ) );
	writeStorageMap( window.localStorage, DISMISSED_STORAGE_KEY, nextMap );
}

function initializeSiteAlerts( root ) {
	const alerts = Array.from( root.querySelectorAll( '.vvm-site-alert' ) );

	alerts.forEach( ( alert ) => {
		const token = alert.dataset.siteMessageToken || '';
		const dismissalExpiry = alert.dataset.siteMessageDismissal || 'permanent';
		const dismissButton = alert.querySelector( '[data-site-message-dismiss]' );

		if ( isDismissed( token, dismissalExpiry ) ) {
			alert.hidden = true;
			return;
		}

		dismissButton?.addEventListener( 'click', () => {
			persistDismissal( token, dismissalExpiry );
			alert.hidden = true;
			syncRootVisibility( root, alerts );
		} );
	} );

	syncRootVisibility( root, alerts );
}

window.addEventListener( 'DOMContentLoaded', () => {
	document
		.querySelectorAll( '[data-site-alerts-root]' )
		.forEach( initializeSiteAlerts );
} );
