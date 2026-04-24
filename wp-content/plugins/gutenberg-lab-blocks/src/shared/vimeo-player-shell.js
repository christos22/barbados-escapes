const REDUCED_MOTION_MEDIA_QUERY = '(prefers-reduced-motion: reduce)';
const VIMEO_SHELL_STATE = new WeakMap();

let vimeoPlayerConstructorPromise;

function getShellState( shell ) {
	let state = VIMEO_SHELL_STATE.get( shell );

	if ( state ) {
		return state;
	}

	state = {
		aspectRatio: 0,
		iframe: null,
		mode: '',
		player: null,
		requestId: 0,
		resizeObserver: null,
		resizeHandler: null,
	};

	VIMEO_SHELL_STATE.set( shell, state );

	return state;
}

function getShellParts( shell ) {
	if ( ! shell ) {
		return null;
	}

	return {
		button: shell.querySelector( '[data-vimeo-play-trigger]' ),
		frameShell: shell.querySelector( '[data-vimeo-frame-shell]' ),
		iframe: shell.querySelector( '[data-vimeo-iframe]' ),
		posterShell: shell.querySelector( '[data-vimeo-poster-shell]' ),
	};
}

function getShellTimeout( shell ) {
	const timeout = Number.parseInt( shell?.dataset?.vimeoTimeout ?? '', 10 );

	return Number.isNaN( timeout ) || timeout < 0 ? 2000 : timeout;
}

function setPosterVisibility( shell, isVisible ) {
	const parts = getShellParts( shell );

	if ( ! parts ) {
		return;
	}

	if ( parts.posterShell ) {
		parts.posterShell.hidden = ! isVisible;
	}

	if ( parts.frameShell ) {
		parts.frameShell.hidden = isVisible;
	}

	shell.dataset.vimeoVisibleState = isVisible ? 'poster' : 'player';
}

function setShellBusy( shell, isBusy ) {
	const button = shell?.querySelector( '[data-vimeo-play-trigger]' );

	if ( button ) {
		button.disabled = isBusy;
	}

	shell.dataset.vimeoBusy = isBusy ? 'true' : 'false';
}

function beginShellRequest( shell ) {
	const state = getShellState( shell );

	state.requestId += 1;

	return state.requestId;
}

function isActiveRequest( shell, requestId ) {
	return getShellState( shell ).requestId === requestId;
}

async function loadVimeoPlayerConstructor() {
	if ( vimeoPlayerConstructorPromise ) {
		return vimeoPlayerConstructorPromise;
	}

	vimeoPlayerConstructorPromise = import( '@vimeo/player' ).then(
		( module ) => module.default || module
	);

	return vimeoPlayerConstructorPromise;
}

async function destroyShellPlayer( state ) {
	if ( ! state?.player || 'function' !== typeof state.player.destroy ) {
		return;
	}

	try {
		await state.player.destroy();
	} catch ( error ) {
		// Vimeo can reject destroy() if the iframe is already gone. That should
		// not block a new player instance from being created.
	}
}

function updateShellFrameFit( shell ) {
	const state = getShellState( shell );
	const iframe = state.iframe;
	const aspectRatio = state.aspectRatio;

	if ( ! iframe || ! aspectRatio ) {
		return;
	}

	const shellRect = shell.getBoundingClientRect();

	if ( ! shellRect.width || ! shellRect.height ) {
		return;
	}

	let targetWidth = shellRect.width;
	let targetHeight = targetWidth / aspectRatio;

	if ( targetHeight < shellRect.height ) {
		targetHeight = shellRect.height;
		targetWidth = targetHeight * aspectRatio;
	}

	iframe.style.width = `${ targetWidth }px`;
	iframe.style.height = `${ targetHeight }px`;
	iframe.style.left = '50%';
	iframe.style.top = '50%';
	iframe.style.transform = 'translate(-50%, -50%)';
}

async function ensureShellAspectRatio( shell, state ) {
	if ( state.aspectRatio ) {
		updateShellFrameFit( shell );
		return;
	}

	const widthMethod = state.player?.getVideoWidth;
	const heightMethod = state.player?.getVideoHeight;

	if (
		'function' !== typeof widthMethod ||
		'function' !== typeof heightMethod
	) {
		state.aspectRatio = 16 / 9;
		updateShellFrameFit( shell );
		return;
	}

	try {
		const [ videoWidth, videoHeight ] = await Promise.all( [
			state.player.getVideoWidth(),
			state.player.getVideoHeight(),
		] );
		const nextAspectRatio = videoWidth && videoHeight ? videoWidth / videoHeight : 0;

		state.aspectRatio = nextAspectRatio || 16 / 9;
	} catch ( error ) {
		state.aspectRatio = 16 / 9;
	}

	updateShellFrameFit( shell );
}

function bindShellResizeObserver( shell ) {
	const state = getShellState( shell );

	if ( state.resizeObserver || state.resizeHandler ) {
		return;
	}

	const updateFit = () => {
		updateShellFrameFit( shell );
	};

	if ( 'undefined' !== typeof ResizeObserver ) {
		state.resizeObserver = new ResizeObserver( updateFit );
		state.resizeObserver.observe( shell );
		return;
	}

	// Older browsers can still keep the iframe centered and cropped by listening
	// to viewport resizes when ResizeObserver is unavailable.
	state.resizeHandler = updateFit;
	window.addEventListener( 'resize', state.resizeHandler );
}

async function ensureShellPlayer( shell, mode ) {
	const state = getShellState( shell );
	const parts = getShellParts( shell );
	const targetUrl =
		'autoplay' === mode
			? shell.dataset.vimeoAutoplayUrl
			: shell.dataset.vimeoManualUrl;

	if ( ! parts?.iframe || ! targetUrl ) {
		return null;
	}

	if (
		state.player &&
		state.mode === mode &&
		state.iframe === parts.iframe &&
		state.iframe?.isConnected
	) {
		return state;
	}

	const VimeoPlayer = await loadVimeoPlayerConstructor();
	const nextIframe = parts.iframe.cloneNode( false );

	nextIframe.src = targetUrl;
	parts.iframe.replaceWith( nextIframe );

	await destroyShellPlayer( state );

	state.iframe = nextIframe;
	state.mode = mode;
	state.player = new VimeoPlayer( nextIframe );

	bindShellResizeObserver( shell );

	return state;
}

async function waitForPlayerReady( player, timeoutMs ) {
	const readyPromise = player
		.ready()
		.then( () => true )
		.catch( () => false );
	const timeoutPromise = new Promise( ( resolve ) => {
		window.setTimeout( () => resolve( false ), timeoutMs );
	} );

	return Promise.race( [ readyPromise, timeoutPromise ] );
}

async function playPlayerInstance( player, isMuted ) {
	if ( ! player ) {
		return false;
	}

	if ( 'boolean' === typeof isMuted && 'function' === typeof player.setVolume ) {
		try {
			await player.setVolume( isMuted ? 0 : 1 );
		} catch ( error ) {
			// Some Vimeo privacy/config states reject volume changes. Playback can
			// still continue, so we do not fail the interaction for that.
		}
	}

	try {
		await player.play();
		return true;
	} catch ( error ) {
		return false;
	}
}

function prefersReducedMotion() {
	return window.matchMedia( REDUCED_MOTION_MEDIA_QUERY ).matches;
}

export function hasVimeoShells( root = document ) {
	return Boolean( root?.querySelector?.( '[data-vimeo-shell]' ) );
}

export function bindVimeoShellPlayButtons( root = document ) {
	root.querySelectorAll( '[data-vimeo-shell]' ).forEach( ( shell ) => {
		const button = shell.querySelector( '[data-vimeo-play-trigger]' );

		if ( ! button || 'true' === button.dataset.vimeoShellBound ) {
			return;
		}

		button.dataset.vimeoShellBound = 'true';
		button.addEventListener( 'click', () => {
			playVimeoShellFromUserAction( shell );
		} );
	} );
}

export async function attemptVimeoShellAutoplay( shell ) {
	if ( ! shell ) {
		return false;
	}

	const requestId = beginShellRequest( shell );

	setPosterVisibility( shell, true );
	setShellBusy( shell, false );

	const state = await ensureShellPlayer( shell, 'autoplay' );

	if ( ! state || ! isActiveRequest( shell, requestId ) ) {
		return false;
	}

	const readyInTime = await waitForPlayerReady(
		state.player,
		getShellTimeout( shell )
	);

	if ( ! readyInTime || ! isActiveRequest( shell, requestId ) ) {
		setPosterVisibility( shell, true );
		return false;
	}

	await ensureShellAspectRatio( shell, state );

	const didPlay = await playPlayerInstance( state.player, true );

	if ( ! didPlay || ! isActiveRequest( shell, requestId ) ) {
		setPosterVisibility( shell, true );
		return false;
	}

	setPosterVisibility( shell, false );

	return true;
}

export async function playVimeoShellFromUserAction( shell ) {
	if ( ! shell ) {
		return false;
	}

	const requestId = beginShellRequest( shell );

	setPosterVisibility( shell, true );
	setShellBusy( shell, true );

	const state = await ensureShellPlayer( shell, 'manual' );

	if ( ! state || ! isActiveRequest( shell, requestId ) ) {
		setShellBusy( shell, false );
		return false;
	}

	const readyInTime = await waitForPlayerReady(
		state.player,
		getShellTimeout( shell )
	);

	if ( ! readyInTime || ! isActiveRequest( shell, requestId ) ) {
		setShellBusy( shell, false );
		setPosterVisibility( shell, true );
		return false;
	}

	await ensureShellAspectRatio( shell, state );

	const didPlay = await playPlayerInstance( state.player, false );

	setShellBusy( shell, false );

	if ( ! didPlay || ! isActiveRequest( shell, requestId ) ) {
		setPosterVisibility( shell, true );
		return false;
	}

	setPosterVisibility( shell, false );

	return true;
}

export async function resetVimeoShell(
	shell,
	{ showPoster = false } = {}
) {
	if ( ! shell ) {
		return;
	}

	beginShellRequest( shell );

	const state = getShellState( shell );

	if ( state.player ) {
		try {
			await state.player.pause();
		} catch ( error ) {
			// Pause can fail before the player finishes loading. The shell should
			// still reset back to its poster state when requested.
		}

		try {
			await state.player.setCurrentTime( 0 );
		} catch ( error ) {
			// Vimeo rejects setCurrentTime() until metadata exists.
		}
	}

	setShellBusy( shell, false );

	if ( showPoster ) {
		setPosterVisibility( shell, true );
	}
}

export function initializeStandaloneVimeoShells( root = document ) {
	const shells = root.querySelectorAll(
		'[data-vimeo-shell][data-vimeo-autoplay-enabled="true"]'
	);

	bindVimeoShellPlayButtons( root );

	if ( prefersReducedMotion() ) {
		shells.forEach( ( shell ) => {
			setPosterVisibility( shell, true );
		} );
		return;
	}

	shells.forEach( ( shell ) => {
		attemptVimeoShellAutoplay( shell );
	} );
}
