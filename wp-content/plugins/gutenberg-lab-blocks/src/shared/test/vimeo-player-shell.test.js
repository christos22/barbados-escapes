import VimeoPlayer from '@vimeo/player';
import { attemptVimeoShellAutoplay, suspendVimeoShell } from '../vimeo-player-shell';

jest.mock( '@vimeo/player', () => jest.fn() );

let shell;
let player;
let events;
const settle = async () => {
	for ( let i = 0; i < 20; i++ ) { await Promise.resolve(); }
};

beforeEach( () => {
	jest.useFakeTimers();
	events = {};
	window.ResizeObserver = jest.fn( () => ( { observe() {} } ) );
	player = {
		paused: true,
		ready: jest.fn( () => Promise.resolve() ),
		on: jest.fn( ( name, callback ) => { events[ name ] = callback; } ),
		getPaused: jest.fn( () => Promise.resolve( player.paused ) ),
		getVideoWidth: jest.fn( () => Promise.resolve( 1920 ) ),
		getVideoHeight: jest.fn( () => Promise.resolve( 1080 ) ),
		setCurrentTime: jest.fn(),
		setMuted: jest.fn( () => Promise.resolve() ),
		setVolume: jest.fn( () => Promise.resolve() ),
		play: jest.fn( () => { player.paused = false; events.play?.(); return Promise.resolve(); } ),
		pause: jest.fn( () => { player.paused = true; return Promise.resolve(); } ),
	};
	VimeoPlayer.mockClear().mockImplementation( () => player );
	document.body.innerHTML = `<div data-vimeo-shell data-vimeo-autoplay-url="https://player.vimeo.com/video/123?autoplay=1" data-vimeo-manual-url="https://player.vimeo.com/video/123">
		<div data-vimeo-frame-shell><iframe data-vimeo-iframe></iframe></div>
		<div data-vimeo-poster-shell></div>
		<button data-vimeo-transport-control data-vvm-video-play-label="Play video" data-vvm-video-pause-label="Pause video"></button>
	</div>`;
	shell = document.querySelector( '[data-vimeo-shell]' );
} );

afterEach( () => { jest.clearAllTimers(); jest.useRealTimers(); } );

async function start() {
	const request = attemptVimeoShellAutoplay( shell );
	await settle();
	await jest.advanceTimersByTimeAsync( 1000 );
	await request;
	player.play.mockClear();
}

test.each( [ true, false ] )( 'preserves Vimeo playback choice and position: playing=%s', async ( wasPlaying ) => {
	await start();
	player.paused = ! wasPlaying;
	const iframe = shell.querySelector( 'iframe' );
	const restore = suspendVimeoShell( shell );
	expect( player.paused ).toBe( true );
	await restore();
	expect( player.play ).toHaveBeenCalledTimes( wasPlaying ? 1 : 0 );
	expect( player.setCurrentTime ).not.toHaveBeenCalled();
	expect( shell.querySelector( 'iframe' ) ).toBe( iframe );
	expect( VimeoPlayer ).toHaveBeenCalledTimes( 1 );
} );

test( 'a delayed iframe autoplay is paused while the lightbox is open', async () => {
	await start();
	suspendVimeoShell( shell );
	player.pause.mockClear();
	player.paused = false;
	events.play();
	expect( player.pause ).toHaveBeenCalledTimes( 1 );
	expect( player.paused ).toBe( true );
} );

test( 'opening during player setup cancels pending autoplay', async () => {
	const request = attemptVimeoShellAutoplay( shell );
	await settle();
	const restore = suspendVimeoShell( shell );
	await jest.advanceTimersByTimeAsync( 1000 );
	await request;
	await restore();
	expect( player.play ).not.toHaveBeenCalled();
	expect( shell.querySelector( 'button' ).dataset.vvmVideoControlState ).toBe( 'paused' );
} );

test( 'an older close cannot resume playback underneath a reopened lightbox', async () => {
	await start();
	const restoreOld = suspendVimeoShell( shell );
	suspendVimeoShell( shell );
	await restoreOld();
	expect( player.play ).not.toHaveBeenCalled();
	expect( player.paused ).toBe( true );
} );
