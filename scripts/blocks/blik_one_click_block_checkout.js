const conotoxiaOneClickSettings = window.wc.wcSettings.getSetting("conotoxia_pay_blik_one_click_data", {});
const conotoxiaOneClickLabel = window.wp.htmlEntities.decodeEntities(conotoxiaOneClickSettings.title);

const ConotoxiaOneClickLabel = () => {
    const icon = conotoxiaOneClickSettings.icon
        ? React.createElement("img", {
            src: conotoxiaOneClickSettings.icon,
            width: 36,
            height: 36,
            style: { display: "inline" },
        })
        : null;

    return React.createElement(
        "span",
        {
            className:
                "wc-block-components-payment-method-label wc-block-components-payment-method-label--with-icon",
        },
        React.createElement(
            "span",
            { style: { marginRight: "10px" } },
            window.wp.htmlEntities.decodeEntities(conotoxiaOneClickSettings.title)
        ),
        icon
    );
};

const formatNoticeMessage = (aliasName) => {
    return conotoxiaOneClickSettings.noticeMessage.replace("%s", aliasName);
};

const ConotxoiaBlikWithoutCodeNotice = ({ aliasName, onClose }) => {
    const [showNotice, setShowNotice] = React.useState(false);
    const title = conotoxiaOneClickSettings.noticeTitle;
    const noticeMessage = formatNoticeMessage(aliasName);
    const noticeButtonLabel = conotoxiaOneClickSettings.noticeButton;

    React.useEffect(() => {
        setShowNotice(!isAcceptance());
    }, []);

    const handleAcceptance = () => {
        setAcceptance();
        setShowNotice(false);
        if (onClose) onClose();
    };

    const isAcceptance = () => {
        const prefix = "cx_blik_one_click_acceptance=";
        const cookieValue = document.cookie
            .split(";")
            .map((cookie) => cookie.trim())
            .find((cookie) => cookie.startsWith(prefix));
        return cookieValue && cookieValue.substring(prefix.length) === "true";
    };

    const setAcceptance = () => {
        const expires = new Date();
        expires.setFullYear(expires.getFullYear() + 1);
        document.cookie = `cx_blik_one_click_acceptance=true;expires=${expires.toUTCString()};path=/`;
    };

    if (!showNotice) return null;

    return window.wp.element.createElement(
        "div",
        {
            id: "js-cx-blik-without-code-notice-background",
            style: {
                display: "block",
                position: "fixed",
                left: 0,
                top: 0,
                zIndex: 2147483647,
                width: "100%",
                height: "100%",
                overflow: "auto",
                backgroundColor: "rgba(0, 0, 0, 0.5)",
            },
        },
        window.wp.element.createElement(
            "div",
            {
                id: "cx-blik-without-code-notice-container",
                style: {
                    display: "flex",
                    flexDirection: "column",
                    alignItems: "center",
                    textAlign: "center",
                    padding: "40px 40px 56px",
                    margin: "40px auto",
                    maxWidth: "600px",
                    gap: "32px",
                    background: "white",
                    borderRadius: "24px",
                },
            },
            window.wp.element.createElement(
                "div",
                {
                    id: "cx-blik-without-code-notice-title",
                    style: {
                        fontWeight: 800,
                        fontSize: "32px",
                        lineHeight: "38px",
                        color: "#333333",
                    },
                },
                title
            ),
            window.wp.element.createElement(
                "div",
                {
                    id: "cx-blik-without-code-notice-message",
                    style: {
                        lineHeight: "24px",
                        color: "#333333",
                    },
                },
                noticeMessage
            ),
            window.wp.element.createElement(
                "button",
                {
                    id: "js-cx-blik-without-code-notice-button",
                    onClick: handleAcceptance,
                    style: {
                        height: "48px",
                        minWidth: "136px",
                        border: "none",
                        borderRadius: "4px",
                        backgroundColor: "#0b49db",
                        color: "white",
                        fontSize: "14px",
                        lineHeight: "16px",
                        cursor: "pointer",
                        whiteSpace: "nowrap",
                    },
                },
                noticeButtonLabel
            )
        )
    );
};

const ConotoxiaAliasItem = ({
                                alias,
                                index,
                                selectedAlias,
                                handleSelectAlias,
                            }) => {
    const uniqueId = `cx-blik-alias-${alias.aliasKey}-${index}`;
    const uniqueValue = `${alias.aliasKey}-${index}`;
    return window.wp.element.createElement(
        "li",
        {
            style: { listStyleType: "none" },
        },
        window.wp.element.createElement("input", {
            type: "radio",
            id: uniqueId,
            name: "aliases",
            value: uniqueValue,
            onChange: handleSelectAlias,
            checked: uniqueValue === selectedAlias,
            style: { outline: "none", boxShadow: "none", background: "transparent" },
        }),
        window.wp.element.createElement(
            "label",
            { htmlFor: uniqueId },
            alias.aliasName
        )
    );
};

const ConotoxiaAliasesList = ({
                                  aliases,
                                  selectedAlias,
                                  handleSelectAlias: handleAliasSelection,
                              }) => {
    return window.wp.element.createElement(
        "ul",
        { style: { paddingLeft: 0 } },
        Array.isArray(aliases) &&
        aliases.map((alias, index) =>
            window.wp.element.createElement(ConotoxiaAliasItem, {
                key: `cx-blik-alias-${alias.aliasKey}-${index}`,
                alias,
                index,
                selectedAlias,
                handleSelectAlias: handleAliasSelection,
            })
        )
    );
};

const ConotoxiaAliasSelection = ({
                                     selectedAliasName,
                                     handleNextClick: handleAliasChangeClick,
                                 }) => {
    const commonStyle = { fontSize: "16px" };
    const changeBlikAliasStyle = {
        ...commonStyle,
        cursor: "pointer",
        textDecoration: "underline",
    };
    const termsAndConditionsStyle = { ...commonStyle, marginTop: "1rem" };

    return window.wp.element.createElement(
        "div",
        { style: commonStyle },
        window.wp.element.createElement(
            "div",
            { style: { display: "flex", flexDirection: "column", gap: "0px" } },
            window.wp.element.createElement(
                "p",
                { style: { ...commonStyle, marginBottom: "0px" } },
                `${conotoxiaOneClickSettings.fromApp} ${selectedAliasName}`
            ),
            window.wp.element.createElement(
                "span",
                { onClick: handleAliasChangeClick, style: changeBlikAliasStyle },
                conotoxiaOneClickSettings.change
            )
        ),
        window.wp.element.createElement(
            "p",
            { style: termsAndConditionsStyle },
            `${conotoxiaOneClickSettings.byPayingYouAccept} `,
            window.wp.element.createElement(
                "a",
                {
                    href: conotoxiaOneClickSettings.termsAndConditionsUrl,
                    style: { textDecoration: "underline" },
                },
                conotoxiaOneClickSettings.termsAndConditionsText
            )
        )
    );
};

const getDefaultAlias = () => {
    return conotoxiaOneClickSettings.defaultAlias?.aliasName
        ? conotoxiaOneClickSettings.defaultAlias
        : conotoxiaOneClickSettings.aliases[0];
};

const shouldShowAliasList = (settings) => {
    return Array.isArray(settings.defaultAlias) && settings.defaultAlias.length === 0;
};

const ConotoxiaOneClickContent = ({
                                      eventRegistration: { onPaymentSetup },
                                      emitResponse,
                                  }) => {
    const defaultAlias = getDefaultAlias();
    const defaultAliasName = defaultAlias.aliasName;
    const defaultAliasValue = `${defaultAlias.aliasKey}-0`;
    const [selectedAlias, setSelectedAlias] = React.useState(defaultAliasValue);
    const [showAliasList, setShowAliasList] = React.useState(shouldShowAliasList(conotoxiaOneClickSettings));
    const [showBlikNotice, setShowBlikNotice] = React.useState(true);

    const handleNextClick = () => setShowAliasList(true);
    const handleSelectAlias = (event) => setSelectedAlias(event.target.value);
    const handleCloseBlikNotice = () => setShowBlikNotice(false);

    React.useEffect(() => {
        const unsubscribe = onPaymentSetup(async () => {
            const userAgent = navigator.userAgent;
            const userScreenResolution = `${screen.width}x${screen.height}`;
            const selectedAliasKey = selectedAlias.split("-")[0];
            return {
                type: emitResponse.responseTypes.SUCCESS,
                meta: {
                    paymentMethodData: {
                        "cx-blik-alias": selectedAliasKey,
                        "cx-user-agent": userAgent,
                        "cx-user-screen-resolution": userScreenResolution,
                    },
                },
            };
        });
        return () => unsubscribe();
    }, [onPaymentSetup, emitResponse.responseTypes.SUCCESS, selectedAlias]);

    return window.wp.element.createElement(
        window.wp.element.Fragment,
        null,
        window.wp.element.createElement(ConotxoiaBlikWithoutCodeNotice, {
            aliasName: defaultAliasName,
            onClose: handleCloseBlikNotice,
        }),
        !showAliasList &&
        window.wp.element.createElement(ConotoxiaAliasSelection, {
            selectedAliasName: defaultAliasName,
            handleNextClick,
        }),
        showAliasList &&
        conotoxiaOneClickSettings.aliases &&
        window.wp.element.createElement(ConotoxiaAliasesList, {
            aliases: conotoxiaOneClickSettings.aliases,
            selectedAlias,
            handleSelectAlias,
        })
    );
};

const ConotoxiaOneClickBlockGateway = {
    name: "conotoxia_pay_blik_one_click",
    label: React.createElement(ConotoxiaOneClickLabel, null),
    content: React.createElement(ConotoxiaOneClickContent, null),
    edit: React.createElement(ConotoxiaOneClickContent, null),
    canMakePayment: () => true,
    paymentMethodId: "conotoxia_pay_blik_one_click",
    ariaLabel: conotoxiaOneClickLabel,
    supports: {
        features: conotoxiaOneClickSettings.supports,
    },
};

window.wc.wcBlocksRegistry.registerPaymentMethod(ConotoxiaOneClickBlockGateway);
