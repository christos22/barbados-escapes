export function getVimeoVideoId( vimeoUrl ) {
	if ( 'string' !== typeof vimeoUrl ) {
		return '';
	}

	const trimmedUrl = vimeoUrl.trim();

	if ( ! trimmedUrl ) {
		return '';
	}

	let parsedUrl;

	try {
		parsedUrl = new URL( trimmedUrl );
	} catch ( error ) {
		return '';
	}

	const host = parsedUrl.hostname.toLowerCase();
	const allowedHosts = new Set( [
		'vimeo.com',
		'www.vimeo.com',
		'player.vimeo.com',
		'www.player.vimeo.com',
	] );

	if ( ! allowedHosts.has( host ) ) {
		return '';
	}

	const pathMatch = parsedUrl.pathname.match( /(?:^|\/)(\d+)(?:$|\/)/ );

	return pathMatch?.[ 1 ] ?? '';
}
