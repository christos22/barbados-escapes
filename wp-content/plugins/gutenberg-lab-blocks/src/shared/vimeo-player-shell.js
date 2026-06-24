import VimeoPlayer from '@vimeo/player';

const REDUCED_MOTION_MEDIA_QUERY = '(prefers-reduced-motion: reduce)';
const MIN_AUTOPLAY_POSTER_MS = 900;
const POSTER_FADE_MS = 350;
const PAGE_INTENT_EVENTS = [
	'focusin',
	'keydown',
	'pointerdown',
	'pointermove',
	'scroll',
	'touchstart',
	'wheel',
];
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
		frameShell: shell.querySelector( '[data-vimeo-frame-shell]' ),
		iframe: shell.querySelector( '[data-vimeo-iframe]' ),
		posterShell: shell.querySelector( '[data-vimeo-poster-shell]' ),
	};
}

function getShellTimeout( shell ) {
	const timeout = Number.parseInt( shell?.dataset?.vimeoTimeout ?? '', 10 );

	return Number.isNaN( timeout ) || timeout < 0 ? 2000 : timeout;
}

function prepareShellIframe( iframe ) {
	if ( ! iframe ) {
		return;
	}

	// Vimeo is the visual media layer here; our real button owns transport
	// controls and must be the keyboard target.
	iframe.tabIndex = -1;
	iframe.setAttribute( 'aria-hidden', 'true' );
	iframe.setAttribute( 'inert', '' );
	iframe.setAttribute( 'tabindex', '-1' );
}

function setPosterVisibility( shell, isVisible ) {
	const parts = getShellParts( shell );

	if ( ! parts ) {
		return;
	}

	if ( parts.frameShell ) {
		parts.frameShell.hidden = isVisible;
	}

	shell.dataset.vimeoVisibleState = isVisible ? 'poster' : 'player';

	if ( parts.posterShell ) {
		parts.posterShell.hidden = false;

		if ( ! isVisible ) {
			window.setTimeout( () => {
				if ( 'player' === shell.dataset.vimeoVisibleState ) {
					parts.posterShell.hidden = true;
				}
			}, POSTER_FADE_MS );
		}
	}
}

function setAutoplayLoadingVisibility( shell ) {
	const parts = getShellParts( shell );

	if ( ! parts ) {
		return;
	}

	if ( parts.posterShell ) {
		parts.posterShell.hidden = false;
	}

	if ( parts.frameShell ) {
		parts.frameShell.hidden = false;
	}

	shell.dataset.vimeoVisibleState = 'loading';
}

function setShellBusy( shell, isBusy ) {
	const control = shell?.querySelector( '[data-vimeo-transport-control]' );

	if ( control ) {
		control.setAttribute( 'aria-disabled', isBusy ? 'true' : 'false' );
	}

	shell.dataset.vimeoBusy = isBusy ? 'true' : 'false';
}

function getVideoControlLabel( control, state ) {
	const labelKey =
		'playing' === state ? 'vvmVideoPauseLabel' : 'vvmVideoPlayLabel';

	return control?.dataset?.[ labelKey ] || '';
}

function setVideoControlElementState( control, state ) {
	if ( ! control ) {
		return;
	}

	const nextState = 'playing' === state ? 'playing' : 'paused';
	const label = getVideoControlLabel( control, nextState );
	const labelElement = control.querySelector( '[data-vvm-video-control-label]' );

	control.dataset.vvmVideoControlState = nextState;

	if ( label ) {
		control.setAttribute( 'aria-label', label );
	}

	if ( labelElement && label ) {
		labelElement.textContent = label;
	}
}

function getNativeVideoForControl( control ) {
	return (
		control?.parentElement?.querySelector( 'video' ) ??
		control?.closest( '[data-vvm-video-frame]' )?.querySelector( 'video' ) ??
		null
	);
}

function getNativeVideoControlsForVideo( video ) {
	if ( ! video ) {
		return [];
	}

	const scopes = [
		video.parentElement,
		video.closest( '[data-vvm-video-frame]' ),
	].filter( Boolean );
	const controls = [];

	scopes.forEach( ( scope ) => {
		scope
			.querySelectorAll( '[data-vvm-native-video-control]' )
			.forEach( ( control ) => {
				if ( getNativeVideoForControl( control ) === video ) {
					controls.push( control );
				}
			} );
	} );

	return Array.from( new Set( controls ) );
}

export function setNativeVideoControlState( video, state ) {
	getNativeVideoControlsForVideo( video ).forEach( ( control ) => {
		setVideoControlElementState( control, state );
	} );
}

function setVimeoTransportControlState( shell, state ) {
	const control = shell?.querySelector( '[data-vimeo-transport-control]' );

	setVideoControlElementState( control, state );
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

	vimeoPlayerConstructorPromise = Promise.resolve( VimeoPlayer );

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

	const nextIframe = parts.iframe.cloneNode( false );
	const previousPlayer = state.player;

	prepareShellIframe( parts.iframe );
	prepareShellIframe( nextIframe );

	nextIframe.src = targetUrl;
	parts.iframe.replaceWith( nextIframe );

	state.iframe = nextIframe;
	state.mode = mode;
	state.player = null;

	await destroyShellPlayer( { player: previousPlayer } );

	const VimeoPlayer = await loadVimeoPlayerConstructor();

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

function wait( timeoutMs ) {
	return new Promise( ( resolve ) => {
		window.setTimeout( resolve, timeoutMs );
	} );
}

async function playPlayerInstance( player, isMuted ) {
	if ( ! player ) {
		return false;
	}

	if ( 'boolean' === typeof isMuted && 'function' === typeof player.setMuted ) {
		try {
			await player.setMuted( isMuted );
		} catch ( error ) {
			// Some Vimeo embeds reject mute state changes. Playback is still worth
			// attempting because the URL may already include the correct policy.
		}
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

function isSaveDataEnabled() {
	return Boolean( window.navigator?.connection?.saveData );
}

function bindPageIntent( callback ) {
	let didRun = false;

	const removeListeners = () => {
		PAGE_INTENT_EVENTS.forEach( ( eventName ) => {
			window.removeEventListener( eventName, handleIntent, true );
		} );
	};

	const handleIntent = ( event ) => {
		if ( didRun || false === event?.isTrusted ) {
			return;
		}

		didRun = true;
		removeListeners();
		callback();
	};

	PAGE_INTENT_EVENTS.forEach( ( eventName ) => {
		window.addEventListener( eventName, handleIntent, {
			capture: true,
			passive: true,
		} );
	} );

	return removeListeners;
}

export function hasVimeoShells( root = document ) {
	return Boolean( root?.querySelector?.( '[data-vimeo-shell]' ) );
}

export function bindVimeoShellTransportControls( root = document ) {
	root.querySelectorAll( '[data-vimeo-shell]' ).forEach( ( shell ) => {
		const control = shell.querySelector( '[data-vimeo-transport-control]' );

		if ( ! control || 'true' === control.dataset.vimeoTransportBound ) {
			return;
		}

		prepareShellIframe( getShellParts( shell )?.iframe );

		control.dataset.vimeoTransportBound = 'true';
		control.setAttribute( 'aria-disabled', 'false' );
		setVimeoTransportControlState(
			shell,
			'player' === shell.dataset.vimeoVisibleState ? 'playing' : 'paused'
		);

		control.addEventListener( 'click', () => {
			if ( 'true' === shell.dataset.vimeoBusy ) {
				return;
			}

			if ( 'playing' === control.dataset.vvmVideoControlState ) {
				pauseVimeoShellFromUserAction( shell );
				return;
			}

			playVimeoShellFromTransportControl( shell );
		} );
	} );
}

async function playVimeoShellFromTransportControl( shell ) {
	const state = getShellState( shell );

	if (
		state.player &&
		state.iframe?.isConnected &&
		'player' === shell.dataset.vimeoVisibleState
	) {
		setShellBusy( shell, true );

		try {
			await state.player.play();
			setVimeoTransportControlState( shell, 'playing' );
		} catch ( error ) {
			setVimeoTransportControlState( shell, 'paused' );
		}

		setShellBusy( shell, false );
		return;
	}

	await playVimeoShellFromUserAction( shell );
}

async function pauseVimeoShellFromUserAction( shell ) {
	const state = getShellState( shell );

	if ( state.player ) {
		try {
			await state.player.pause();
		} catch ( error ) {
			// Vimeo can reject pause while an iframe is still initializing.
		}
	}

	setVimeoTransportControlState( shell, 'paused' );
}

export function bindNativeVideoControls( root = document ) {
	root.querySelectorAll( '[data-vvm-native-video-control]' ).forEach(
		( control ) => {
			if ( 'true' === control.dataset.vvmNativeVideoControlBound ) {
				return;
			}

			const video = getNativeVideoForControl( control );

			if ( ! video ) {
				return;
			}

			const syncState = ( { optimisticAutoplay = false } = {} ) => {
				const shouldTreatAsPlaying =
					! video.paused ||
					( optimisticAutoplay && video.autoplay && ! video.ended );

				setVideoControlElementState(
					control,
					shouldTreatAsPlaying ? 'playing' : 'paused'
				);
			};

			control.dataset.vvmNativeVideoControlBound = 'true';
			syncState( { optimisticAutoplay: true } );

			video.addEventListener( 'play', syncState );
			video.addEventListener( 'playing', syncState );
			video.addEventListener( 'pause', syncState );
			video.addEventListener( 'ended', syncState );

			control.addEventListener( 'click', () => {
				if ( video.paused || video.ended ) {
					const playPromise = video.play?.();

					if ( playPromise && 'function' === typeof playPromise.then ) {
						playPromise
							.then( () => syncState() )
							.catch( () => syncState() );
						return;
					}

					syncState();
					return;
				}

				video.pause?.();
				syncState();
			} );
		}
	);
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

	// Keep the poster visible while the muted iframe preloads underneath it.
	// This avoids a blank hero, but still lets the Vimeo embed start on load.
	if ( ! state.aspectRatio ) {
		state.aspectRatio = 16 / 9;
	}

	updateShellFrameFit( shell );
	setAutoplayLoadingVisibility( shell );

	const [ readyInTime ] = await Promise.all( [
		waitForPlayerReady( state.player, getShellTimeout( shell ) ),
		wait( MIN_AUTOPLAY_POSTER_MS ),
	] );

	if ( ! readyInTime || ! isActiveRequest( shell, requestId ) ) {
		if ( isActiveRequest( shell, requestId ) ) {
			setPosterVisibility( shell, false );
			setVimeoTransportControlState( shell, 'playing' );
		}

		return readyInTime;
	}

	await ensureShellAspectRatio( shell, state );

	const didPlay = await playPlayerInstance( state.player, true );

	if ( ! didPlay || ! isActiveRequest( shell, requestId ) ) {
		setPosterVisibility( shell, true );
		setVimeoTransportControlState( shell, 'paused' );
		return false;
	}

	setPosterVisibility( shell, false );
	setVimeoTransportControlState( shell, 'playing' );

	return true;
}

async function playVimeoShellFromUserAction( shell ) {
	if ( ! shell ) {
		return false;
	}

	const requestId = beginShellRequest( shell );

	setPosterVisibility( shell, true );
	setShellBusy( shell, true );

	const state = await ensureShellPlayer( shell, 'manual' );

	if ( ! state || ! isActiveRequest( shell, requestId ) ) {
		setShellBusy( shell, false );
		setVimeoTransportControlState( shell, 'paused' );
		return false;
	}

	const readyInTime = await waitForPlayerReady(
		state.player,
		getShellTimeout( shell )
	);

	if ( ! readyInTime || ! isActiveRequest( shell, requestId ) ) {
		setShellBusy( shell, false );
		setPosterVisibility( shell, true );
		setVimeoTransportControlState( shell, 'paused' );
		return false;
	}

	await ensureShellAspectRatio( shell, state );

	let didPlay = await playPlayerInstance( state.player, false );

	if ( ! didPlay ) {
		didPlay = await playPlayerInstance( state.player, true );
	}

	setShellBusy( shell, false );

	if ( ! didPlay || ! isActiveRequest( shell, requestId ) ) {
		setPosterVisibility( shell, true );
		setVimeoTransportControlState( shell, 'paused' );
		return false;
	}

	setPosterVisibility( shell, false );
	setVimeoTransportControlState( shell, 'playing' );

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

	setVimeoTransportControlState( shell, 'paused' );
}

export function initializeStandaloneVimeoShells(
	root = document,
	{ autoplayStrategy = 'eager' } = {}
) {
	const shells = root.querySelectorAll(
		'[data-vimeo-shell][data-vimeo-autoplay-enabled="true"]'
	);

	bindVimeoShellTransportControls( root );

	if ( prefersReducedMotion() || isSaveDataEnabled() ) {
		shells.forEach( ( shell ) => {
			setPosterVisibility( shell, true );
		} );
		return;
	}

	if ( 'intent' === autoplayStrategy ) {
		bindPageIntent( () => {
			shells.forEach( ( shell ) => {
				attemptVimeoShellAutoplay( shell );
			} );
		} );
		return;
	}

	shells.forEach( ( shell ) => {
		attemptVimeoShellAutoplay( shell );
	} );
}
