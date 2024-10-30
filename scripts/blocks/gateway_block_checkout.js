const conotoxiaGatewaySettings = window.wc.wcSettings.getSetting("conotoxia_pay_data", {});
const conotoxiaGatewayLabel = window.wp.htmlEntities.decodeEntities(conotoxiaGatewaySettings.title);

const ConotoxiaGatewayLabel = ({ paymentIcons }) => {
    const titleWrapper = React.createElement(
        "div",
        {
            style: {
                display: "inline-flex",
                alignItems: "center",
                marginRight: "10px",
            },
        },
        React.createElement(
            "div",
            null,
            String(window.wp.htmlEntities.decodeEntities(conotoxiaGatewaySettings.title))
        )
    );

    const iconWrapper = conotoxiaGatewaySettings.icon ? React.createElement(
        "div",
        {
            style: { display: "inline-flex", margin: "0", padding: "0" },
        },
        React.createElement("img", {
            src: conotoxiaGatewaySettings.icon,
            width: 90,
            height: 90,
        })
    ) : null;

    const iconTitleContainer = React.createElement(
        "div",
        {
            style: { display: "flex", alignItems: "center" },
        },
        titleWrapper,
        iconWrapper
    );

    const paymentIconsElement = React.createElement(ConotoxiaPaymentMethodsIcons, {
        icons: paymentIcons,
    });
    const iconsContainer = React.createElement(
        "div",
        { style: { marginTop: "10px" } },
        paymentIconsElement
    );

    return React.createElement(
        "span",
        {
            className:
                "wc-block-components-payment-method-label wc-block-components-payment-method-label--with-icon",
        },
        iconTitleContainer,
        iconsContainer
    );
};

const ConotoxiaPaymentMethodsIcons = ({ icons }) => {
    if (!icons || icons.length === 0) {
        return null;
    }
    return window.wp.element.createElement(
        window.wp.element.Fragment,
        null,
        icons.map((icon) => {
            return window.wp.element.createElement("img", { src: icon.src, alt: icon.title });
        })
    );
};

const ConotoxiaGatewayContent = () => {
    return window.wp.element.createElement(
        window.wp.element.Fragment,
        null,
        window.wp.htmlEntities.decodeEntities(conotoxiaGatewaySettings.description)
    );
};

const ConotoxiaGatewayBlock = {
    name: "conotoxia_pay",
    label: Object(window.wp.element.createElement)(ConotoxiaGatewayLabel, {
        paymentIcons: conotoxiaGatewaySettings.icons,
    }),
    content: Object(window.wp.element.createElement)(ConotoxiaGatewayContent, null),
    edit: Object(window.wp.element.createElement)(ConotoxiaGatewayContent, null),
    canMakePayment: () => true,
    paymentMethodId: "conotoxia_pay",
    ariaLabel: conotoxiaGatewayLabel,
    supports: {
        features: conotoxiaGatewaySettings.supports,
    },
};

window.wc.wcBlocksRegistry.registerPaymentMethod(ConotoxiaGatewayBlock);