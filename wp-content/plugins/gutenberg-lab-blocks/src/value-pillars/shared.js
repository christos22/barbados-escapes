export const VALUE_PILLAR_DEFINITIONS = [
	{
		slug: 'curated',
		title: 'Personally Curated',
		dashicon: 'dashicons-star-filled',
		description:
			'Every property in our collection has been personally selected. We know each villa intimately and only recommend what we would stay in ourselves.',
	},
	{
		slug: 'transparency',
		title: 'Transparency',
		dashicon: 'dashicons-visibility',
		description:
			"No hidden fees. No surprises. Every villa is priced clearly and we will always be honest about what a property offers - and what it doesn't. We would rather find you the right villa than the wrong one.",
	},
	{
		slug: 'knowledge',
		title: 'Insider Knowledge',
		dashicon: 'dashicons-location-alt',
		description:
			'We know this market intimately. That means we know which properties truly deliver on their promise, which ones to avoid, and where to find the hidden gems that most visitors never discover. Our network is your advantage.',
	},
	{
		slug: 'availability',
		title: 'Always Available',
		dashicon: 'dashicons-clock',
		description:
			'Whether you are planning from home or already on the island, we are always on hand. When something needs attention we are here. You are never dealing with a call centre or an automated system. Just us.',
	},
	{
		slug: 'service',
		title: 'White Glove Service',
		dashicon: 'dashicons-admin-users',
		description:
			"From airport transfers to private chefs, restaurant reservations to island experiences - we handle every detail so you don't have to.",
	},
	{
		slug: 'privacy',
		title: 'Discretion',
		dashicon: 'dashicons-lock',
		description:
			'Our clients value their privacy. We handle every enquiry and booking with complete discretion. Your details are never shared and your stay is entirely your own.',
	},
];

export function getValuePillarDefinition( slug ) {
	return (
		VALUE_PILLAR_DEFINITIONS.find( ( definition ) => definition.slug === slug ) ??
		VALUE_PILLAR_DEFINITIONS[ 0 ]
	);
}

export function getValuePillarDashiconClass( slug ) {
	return getValuePillarDefinition( slug ).dashicon;
}
