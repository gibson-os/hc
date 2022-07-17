Ext.define('GibsonOS.module.hc.warehouse.box.item.Form', {
    extend: 'GibsonOS.module.core.component.form.Panel',
    alias: ['widget.gosModuleHcWarehouseBoxItemForm'],
    requiredPermission: {
        action: 'save',
        permission: GibsonOS.Permission.MANAGE + GibsonOS.Permission.WRITE
    },
    initComponent() {
        const me = this;

        me.items = [{
            xtype: 'gosCoreComponentPanel',
            enableToolbar: false,
            itemId: 'image',
            cls: 'coloredPanel',
            data: {
                itemId: 0,
                image: '',
                src: ''
            },
            tpl: new Ext.XTemplate(
                '<div class="hcWarehouseBoxImage" style="height: 290px; background-image: url(<tpl if="src">{src}<tpl else>' + baseDir + 'hc/warehouse/image/id/{itemId}/{image}</tpl>);"></div>'
            )
        },{
            xtype: 'gosCoreComponentFormFieldTextField',
            fieldLabel: 'Name',
            name: 'name'
        },{
            xtype: 'gosCoreComponentFormFieldNumberField',
            fieldLabel: 'Anzahl',
            name: 'stock',
            minValue: 0
        },{
            xtype: 'gosCoreComponentFormFieldTextArea',
            fieldLabel: 'Beschreibung',
            name: 'description'
        },{
            xtype: 'gosModuleHcWarehouseBoxItemTabPanel',
            moduleId: me.moduleId
        }];

        me.callParent();

        const image = me.down('#image');

        image.on('render', (imagePanel) => {
            const element = imagePanel.getEl().dom;
            const stopEvents = (event) => {
                event.stopPropagation();
                event.preventDefault();
            };
            element.ondragover = stopEvents;
            element.ondrageleave = stopEvents;
            element.ondrop = (event) => {
                stopEvents(event);

                const file = event.dataTransfer.files[0];
                const reader = new FileReader();

                reader.readAsDataURL(file);
                reader.onload = () => {
                    let data = image.initialConfig.data;

                    data.src = reader.result;
                    data.itemId = image.itemId;
                    image.update(data);
                    image.fireEvent('imageUploaded', image, data.src, file);
                };
            };
        });
    }
});