Ext.define('GibsonOS.module.hc.blueprint.View', {
    extend: 'GibsonOS.module.core.component.Panel',
    alias: ['widget.gosModuleHcBlueprintView'],
    requiredPermission: {
        module: 'hc',
        task: 'blueprint'
    },
    cls: 'coloredPanel',
    overflowX: 'auto',
    overflowY: 'auto',
    data: [],
    initComponent() {
        let me = this;

        //me.tpl = new Ext.XTemplate('<img src="' + baseDir + 'hc/blueprint/svg?id=1&childrenTypes[]=FRAME&childrenTypes[]=ROOM&childrenTypes[]=FURNISHING&childrenTypes[]=MODULE" />');
        // me.tpl = new Ext.XTemplate('<object id="svg-object" data="' + baseDir + 'hc/blueprint/svg?id=1&childrenTypes[]=FRAME&childrenTypes[]=ROOM&childrenTypes[]=FURNISHING&childrenTypes[]=MODULE" type="image/svg+xml"></object>');

        me.callParent(arguments);

        me.addAction({
            xtype: 'gosModuleCoreParameterTypeAutoComplete',
            itemId: 'hcBlueprintViewAutoComplete',
            hideLabel: true,
            width: 150,
            enableKeyEvents: true,
            emptyText: 'Grundriss laden',
            addToItemContextMenu: false,
            addToContainerContextMenu: false,
            requiredPermission: {
                action: 'svg',
                method: 'GET',
                permission: GibsonOS.Permission.READ
            },
            parameterObject: {
                config: {
                    model: 'GibsonOS.module.hc.blueprint.model.Blueprint',
                    autoCompleteClassname: 'GibsonOS\\Module\\Hc\\AutoComplete\\BlueprintAutoComplete'
                }
            },
            listeners: {
                select(combo, records) {
                    me.loadSvg();
                }
            }
        });
        me.addAction({
            iconCls: 'icon_system system_filter',
            menu: [{
                xtype: 'menucheckitem',
                checked: true,
                itemId: 'hcBlueprintViewFilterFrame',
                text: 'Rahmen',
                checkHandler() {
                    me.loadSvg();
                }
            },{
                xtype: 'menucheckitem',
                checked: true,
                itemId: 'hcBlueprintViewFilterRoom',
                text: 'Räume',
                checkHandler() {
                    me.loadSvg();
                }
            },{
                xtype: 'menucheckitem',
                checked: true,
                itemId: 'hcBlueprintViewFilterFurnishing',
                text: 'Einrichtungen',
                checkHandler() {
                    me.loadSvg();
                }
            },{
                xtype: 'menucheckitem',
                checked: true,
                itemId: 'hcBlueprintViewFilterModule',
                text: 'Module',
                checkHandler() {
                    me.loadSvg();
                }
            },('-'),{
                xtype: 'menucheckitem',
                checked: false,
                itemId: 'hcBlueprintViewFilterDimensions',
                text: 'Mit Dimensionen',
                checkHandler() {
                    me.loadSvg();
                }
            }]
        });
    },
    loadSvg() {
        const me = this;
        const id = me.down('#hcBlueprintViewAutoComplete').getValue();

        if (id === null) {
            return;
        }

        me.setLoading(true);

        let childrenTypes = [];

        const addChildrenType = (itemId, key) => {
            if (me.down('#' + itemId).checked) {
                childrenTypes.push(key);
            }
        };
        addChildrenType('hcBlueprintViewFilterFrame', 'FRAME');
        addChildrenType('hcBlueprintViewFilterRoom', 'ROOM');
        addChildrenType('hcBlueprintViewFilterFurnishing', 'FURNISHING');
        addChildrenType('hcBlueprintViewFilterModule', 'MODULE');

        Ext.Ajax.request({
            url: baseDir + 'hc/blueprint/svg',
            method: 'GET',
            params:  {
                id: id,
                'childrenTypes[]': childrenTypes,
                withDimensions: me.down('#hcBlueprintViewFilterDimensions').checked
            },
            success(response) {
                me.update(response.responseText);

                Ext.iterate(document.querySelectorAll('#' + me.id + ' svg *[data-module-id]'), (geometry) => {
                    geometry.onclick = function() {
                        console.log(this);
                    };
                });

                me.setLoading(false);
            }
        });
    }
});