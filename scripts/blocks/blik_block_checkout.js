const conotoxiaBlikSettings = window.wc.wcSettings.getSetting("conotoxia_pay_blik_data", {});
const conotoxiaBlikLabel = window.wp.htmlEntities.decodeEntities(conotoxiaBlikSettings.title);
const conotoxiaDecodedBlikSettings = window.wp.htmlEntities.decodeEntities(conotoxiaBlikSettings);
const conotoxiaPluginBlikBlockName = 'conotoxia_pay_blik';

const conotoxiaBlikStyles = {
    blikCodeInput: {
        height: "2em",
        width: "7em",
        margin: 0,
        padding: 0,
        fontSize: "16px",
        letterSpacing: "1px",
        textAlign: "center",
        border: "1px solid #D3DCE3",
        borderRadius: "4px",
        caretColor: "transparent",
        outline: "none",
        borderColor: "initial",
    },
    blikCodeLabel: {
        fontSize: "16px",
    },
    blikCodeInstruction: {
        fontSize: "14px",
    },
    blikCodeTerms: {
        marginTop: "1rem",
        fontSize: "16px",
    },
};

const ConotoxiaBlikInputComponent = ({ blikInput, setBlikInput }) => {
    const formatBlikCode = (value) => {
        let onlyNums = value.replace(/\D/g, "");
        if (onlyNums.length > 6) onlyNums = onlyNums.slice(0, 6);
        if (onlyNums.length > 3)
            onlyNums = `${onlyNums.slice(0, 3)} ${onlyNums.slice(3)}`;
        return onlyNums;
    };

    const BlikInputChangeHandler = (event) => {
        const formattedValue = formatBlikCode(event.target.value);
        setBlikInput(formattedValue);
    };

    const inputStyle = {
        ...conotoxiaBlikStyles.blikCodeInput,
    };

    const blikCodeInput = React.createElement("input", {
        type: "text",
        name: `${conotoxiaPluginBlikBlockName}_blik_code`,
        id: `${conotoxiaPluginBlikBlockName}_blik`,
        placeholder: "___ ___",
        autoComplete: "off",
        required: true,
        pattern: "[0-9]*",
        inputMode: "numeric",
        onChange: BlikInputChangeHandler,
        value: blikInput,
        style: inputStyle,
    });

    const blikLabelElement = React.createElement(
        "label",
        {
            htmlFor: `${conotoxiaPluginBlikBlockName}_blik`,
            style: conotoxiaBlikStyles.blikCodeLabel,
        },
        conotoxiaDecodedBlikSettings.description || ""
    );

    const blikCodeInstruction = React.createElement(
        "div",
        {
            style: conotoxiaBlikStyles.blikCodeInstruction,
        },
        conotoxiaDecodedBlikSettings.blikCodeInstruction
    );

    const blikCodeTerms = window.wp.element.createElement(
        "div",
        { style: conotoxiaBlikStyles.blikCodeTerms },
        conotoxiaDecodedBlikSettings.byPayingYouAccept + " ",
        window.wp.element.createElement(
            "a",
            {
                href: conotoxiaDecodedBlikSettings.termsAndConditionsUrl,
                style: { textDecoration: "underline" },
            },
            conotoxiaDecodedBlikSettings.termsAndConditionsText
        )
    );

    return React.createElement(
        "div",
        null,
        blikCodeInput,
        blikLabelElement,
        blikCodeInstruction,
        blikCodeTerms
    );
};

const ConotoxiaBlikContent = (props) => {
    const [blikInput, setBlikInput] = React.useState("");
    const { eventRegistration, emitResponse } = props;
    const { onPaymentSetup } = eventRegistration;

    React.useEffect(() => {
        return onPaymentSetup(async () => {
            const blikCode = blikInput.replace(" ", "");
            if (blikCode.length === 0) {
                return {
                    type: emitResponse.responseTypes.ERROR,
                    message: conotoxiaDecodedBlikSettings.emptyBlikCode,
                };
            }
            if (blikCode.length < 6) {
                return {
                    type: emitResponse.responseTypes.ERROR,
                    message: conotoxiaDecodedBlikSettings.invalidBlikCode,
                };
            }
            const userAgent = navigator.userAgent;
            const userScreenResolution = `${screen.width}x${screen.height}`;

            const validBlikCodeValue = blikCode.length === 6;

            if (validBlikCodeValue) {
                return {
                    type: emitResponse.responseTypes.SUCCESS,
                    meta: {
                        paymentMethodData: {
                            "cx-blik-code": blikCode,
                            "cx-user-agent": userAgent,
                            "cx-user-screen-resolution": userScreenResolution,
                        },
                    },
                };
            }
            return {
                type: emitResponse.responseTypes.ERROR,
                message: "Unexpected error",
            };
        });
    }, [
        emitResponse.responseTypes.ERROR,
        emitResponse.responseTypes.SUCCESS,
        onPaymentSetup,
        blikInput,
    ]);

    const description = React.createElement(
        "p",
        null,
        conotoxiaDecodedBlikSettings.description || ""
    );

    return React.createElement(
        "div",
        null,
        description,
        React.createElement(ConotoxiaBlikInputComponent, {
            blikInput,
            setBlikInput
        })
    );
};

const ConotoxiaBlikLabel = (props) => {
    const icon = conotoxiaBlikSettings.icon ? React.createElement("img", {
        src: conotoxiaBlikSettings.icon,
        width: 36,
        height: 36,
        style: { display: "inline" },
    }) : null;
    return React.createElement(
        "span",
        {
            className: "wc-block-components-payment-method-label wc-block-components-payment-method-label--with-icon",
        },
        React.createElement(
            "span",
            { style: { marginRight: "10px" } },
            conotoxiaDecodedBlikSettings.title
        ),
        icon
    );
};

const ConotoxiaBlikBlock = {
    name: conotoxiaPluginBlikBlockName,
    label: React.createElement(ConotoxiaBlikLabel, null),
    content: React.createElement(ConotoxiaBlikContent, null),
    edit: React.createElement(ConotoxiaBlikContent, null),
    canMakePayment: () => true,
    paymentMethodId: conotoxiaPluginBlikBlockName,
    ariaLabel: conotoxiaBlikLabel,
    supports: {
        features: conotoxiaBlikSettings.supports,
    },
};

window.wc.wcBlocksRegistry.registerPaymentMethod(ConotoxiaBlikBlock);