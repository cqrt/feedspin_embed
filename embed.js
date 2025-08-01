function embedOriginalArticle(id) {
    // Check sandbox support immediately
    if (!("sandbox" in document.createElement("iframe"))) {
        alert(__("Sorry, your browser does not support sandboxed iframes."));
        return;
    }

    // Find content container with better error handling
    let container = null;
    try {
        if (App.isCombinedMode()) {
            container = document.querySelector(`div#RROW-${id} .content-inner`);
        } else if (id == Article.getActive()) {
            container = document.querySelector(".post .content");
        }
    } catch (e) {
        console.error("Error finding content container:", e);
        return;
    }

    if (!container) {
        console.warn("Content container not found for article", id);
        return;
    }

    // Handle existing iframe case
    const parent = container.parentNode;
    const existingIframe = parent.querySelector(".embeddedContent");
    
    if (existingIframe) {
        Element.show(container);
        parent.removeChild(existingIframe);
        
        if (App.isCombinedMode()) {
            Article.cdmMoveToId(id, true);
        }
        return;
    }

    // Prepare and send request
    const query = {
        op: "pluginhandler",
        plugin: "feedspin_embed",
        method: "getUrl",
        id: id
    };

    xhrJson("backend.php", query, (reply) => {
        if (!reply || !reply.url) {
            console.error("Invalid response for article embedding", id, reply);
            return;
        }

        // Calculate dimensions safely
        const width = Math.max(parent.offsetWidth - 5, 100);
        const headerHeight = parent.firstChild ? parent.firstChild.offsetHeight : 40;
        const height = Math.max(parent.parentNode.offsetHeight - headerHeight - 5, 300);
        const minHeight = Math.max(document.body.clientHeight - 115, 300);

        // Create iframe with proper attributes
        const iframe = new Element("iframe", {
            class: "embeddedContent",
            src: reply.url,
            allow: "autoplay; fullscreen",
            width: width + 'px',
            height: height + 'px',
            style: `overflow: auto; border: none; min-height: ${minHeight}px;`,
            sandbox: "allow-same-origin allow-scripts allow-presentation",
            loading: "lazy"
        });

        // Toggle visibility and insert
        Element.hide(container);
        parent.insertBefore(iframe, container);

        if (App.isCombinedMode()) {
            Article.cdmMoveToId(id, true);
        }
    }, (error) => {
        console.error("Embed request failed:", error);
        alert(__("Failed to load article content."));
    });
}
