module.exports = {
    build: {
        templates: {
            source: "resources/mail/templates",
            destination: {
                path: "build/mail",
            },
            assets: {
                source: "resources/mail/assets",
                destination: "build/mail/assets",
            },
        },
        tailwind: {
            config: "tailwind.maizzle.js",
        },
    },
};
