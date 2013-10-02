LogHandler = Class.create();
LogHandler.prototype = Object.extend(new CommonHandler(), {

    //----------------------------------

    initialize: function() {},

    //----------------------------------

    showFullText: function(element)
    {
        var content = element.next().innerHTML;
        var title = M2ePro.translator.translate('Description');

        Dialog.info(content, {
            draggable: true,
            resizable: true,
            closable: true,
            className: "magento",
            windowClassName: "popup-window",
            title: title,
            width: 300,
            top: 200,
            zIndex: 100,
            recenterAuto: false,
            hideEffect: Element.hide,
            showEffect: Element.show
        });
    }

    //----------------------------------
});