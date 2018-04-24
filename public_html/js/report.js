const Handlers = {
    baseLimit: 5,
    storage: {
        wallet: [],
        wallet_log: []
    },
    paramsByNameTable: {
        grid_wallet: {
            offset: 0,
            limit: 5
        },
        grid_wallet_log: {
            offset: 0,
            limit: 5,
            dt_start: '',
            dt_end: '',
            is_get_sum: 0,
            wallet_id: ''
        }
    },
    basePathByKey: {
        grid_wallet: '/api/wallets?',
        grid_wallet_log: '/api/wallets_log?'
    },
    handlersName: {
        grid_wallet: 'loadWallets',
        grid_wallet_log: 'loadLogs'
    },
    currencyKeyByWalletId: { },
    makeUrl: function(table)
    {
        if (!table) {return;}
        return Handlers.basePathByKey[table] +  jQuery.param( Handlers.paramsByNameTable[table] );
    },
    makeUrlExport: function(table)
    {
        if (!table) {return;}

        Handlers.paramsByNameTable[table]['count'] = Handlers.paramsByNameTable[table].limit + Handlers.paramsByNameTable[table].offset;
        return '/site/export?table=' + table + '&' + jQuery.param( Handlers.paramsByNameTable[table] );
    },
    loadLogs : function()
    {
        Handlers.paramsByNameTable['grid_wallet_log'].is_get_sum = 0;
        w2ui.grid_wallet_log.load(Handlers.makeUrl('grid_wallet_log'),
            function(reply)
            {
                reply.records.forEach(function(item) {
                    item["currency_sum"] += " " + item["currency_key"];
                    item["usd_sum"] += " usd";
                    item["recid"] = item["id"];
                    Handlers.storage['wallet_log'].push(item);
                });

                w2ui.grid_wallet_log.records = [];
                w2ui.grid_wallet_log.add(Handlers.storage['wallet_log']);

                Handlers.loadSumLogs();
            }
        );
    },
    loadSumLogs: function()
    {
        w2ui.logs_sum.clear();
        Handlers.paramsByNameTable['grid_wallet_log'].is_get_sum = 1;
        w2ui.logs_sum.load(Handlers.makeUrl('grid_wallet_log'),
            function(data)
            {
                if (!data.records || !data.records[0] || !data.records[1]) {
                    return false;
                }

                w2ui.grid_wallet_log.add([{ w2ui: { summary: true },
                    recid: 'S-1',
                    description: '<span style="float: right;">Total</span>',
                    currency_sum: data.records[0] + Handlers.currencyKeyByWalletId[Handlers.paramsByNameTable['grid_wallet_log'].wallet_id],
                    usd_sum: data.records[1] + " usd"
                }]);

                w2ui.grid_wallet_log.refresh();
            }
        );
    },
    loadWallets: function(data)
    {
        Handlers.currencyKeyByWalletId = {};
        data.records.forEach(function(item) {
            item["amount_currency"] = item.amount + " " + item.currency_key;
            item["recid"] = item["id"];
            if (!Handlers.currencyKeyByWalletId[item["id"]]) {
                Handlers.currencyKeyByWalletId[item["id"]] = " " + item["currency_key"];
            }

            Handlers.storage['wallet'].push(item);
        });

        w2ui.grid_wallet.records = [];
        w2ui.grid_wallet.add(Handlers.storage['wallet']);
    },
    loadMore: function(e)
    {
        const nameTable = $(e.currentTarget).data('load_table');
        if (w2ui[nameTable]) {
            Handlers.paramsByNameTable[nameTable].offset += Handlers.paramsByNameTable[nameTable].limit;
            Handlers.paramsByNameTable['grid_wallet_log'].is_get_sum = 0;
            w2ui[nameTable].load(Handlers.basePathByKey[nameTable], Handlers[Handlers.handlersName[nameTable]]);
        }
    },
    initHandler: function ()
    {
        $(document).on('click', '[data-load_table]', Handlers.loadMore);

        $('[data-export_table="grid_wallet"]').prop("href", Handlers.makeUrlExport("grid_wallet"));
        $('[data-export_table="grid_wallet_log"]').prop("href", Handlers.makeUrlExport("grid_wallet_log"));

        $('input[type=dt_start]').w2field('datetime', { format: 'yyyy-mm-dd|hh24:mm:ss', end: $('input[type=dt_end]') });
        $('input[type=dt_end]').w2field('datetime', { format: 'yyyy-mm-dd|hh24:mm:ss', start: $('input[type=dt_start]')});

        $('[data-reset]').on('click', function (e) {
            Handlers.paramsByNameTable['grid_wallet_log'].dt_start = '';
            Handlers.paramsByNameTable['grid_wallet_log'].dt_end = '';
            Handlers.paramsByNameTable['grid_wallet_log'].offset = 0;
            Handlers.storage['wallet_log'] = [];
            $('input[type=dt_start]').val('');
            $('input[type=dt_end]').val('');
            Handlers.loadLogs();
        });

        $('[data-filter]').on('click', function (e) {
            Handlers.paramsByNameTable['grid_wallet_log'].dt_start = $('input[type=dt_start]').val();
            Handlers.paramsByNameTable['grid_wallet_log'].dt_end = $('input[type=dt_end]').val();
            Handlers.paramsByNameTable['grid_wallet_log'].offset = 0;
            Handlers.storage['wallet_log'] = [];
            Handlers.loadLogs();
        });
    }
};

w2utils.locale({
    "dateFormat"        : "yyyy-mm-dd",
    "timeFormat"        : "hh24:mm:ss",
    "datetimeFormat"    : "yyyy-mm-dd|hh24:mm:ss"
});

const pstyle = 'border: 1px solid #dfdfdf; padding: 5px;';

$('#layout').w2layout({
    name: 'layout',
    panels: [
        { type: 'left', size: '30%', style: pstyle,
            content: '<div id="wallets" style="width: 100%; height: 350px;"></div>' +
            '<div class="table_control"><button data-load_table="grid_wallet">Load more</button>' +
            '<a href="" target="_blank" data-export_table="grid_wallet"><button>Export wallets to csv</button></a></div>'
        },
        { type: 'main', size: '70%', style: pstyle,
            content: '<div id="logs" style="width: 100%; height: 295px;"></div>' +
            '<div class="datetime_wrap"><span>Select datetime interval</span><input type="dt_start"> - <input type="dt_end">' +
            '<button data-filter="grid_wallet_log">Filter</button><button data-reset="grid_wallet_log">Reset</button></div>' +
            '<div class="table_control"><button data-load_table="grid_wallet_log">Load more</button>' +
            '<a href="" target="_blank" data-export_table="grid_wallet_log"><button>Export operation to csv</button></a></div>'
        }
    ]
});

$('#wallets').w2grid({
    name: 'grid_wallet',
    method: 'GET',
    limit: Handlers.baseLimit,
    show: {
        footer    : true,
        toolbar    : false,
        toolbarReload: false
    },
    autoLoad: false,
    multiSearch: false,
    searches: [
        { field: 'full_name', caption: 'Full name', type: 'text' }
    ],
    columns: [
        { field: 'full_name', caption: 'Full Name', size: '50%' },
        { field: 'amount_currency', caption: 'Amount', size: '50%' }
    ],
    onClick: function(event)
    {
        if (event && event.recid) {
            Handlers.paramsByNameTable['grid_wallet_log'].wallet_id = event.recid;
            Handlers.paramsByNameTable['grid_wallet_log'].offset = 0;
            Handlers.storage['wallet_log'] = [];
        }
        w2ui.grid_wallet_log.clear();
        Handlers.loadLogs();
    },
    onDblClick: function() { return; },
    onRequest: function(data)
    {
        $('[data-export_table="grid_wallet"]').prop("href", Handlers.makeUrlExport("grid_wallet"));
        data.url = Handlers.makeUrl('grid_wallet');
    }
});

w2ui.grid_wallet.load(Handlers.makeUrl('grid_wallet'), Handlers.loadWallets);

$('#logs').w2grid({
    name: 'grid_wallet_log',
    method: 'GET',
    show: {
        footer    : true,
        toolbarReload: false,
        toolbar: false
    },
    multiSearch: false,
    limit: Handlers.baseLimit,
    columns: [
        { field: 'dt', caption: 'Operation date', size: '15%' },
        { field: 'description', caption: 'Description', size: '45%' },
        { field: 'currency_sum', caption: 'Sum currency', size: '20%' },
        { field: 'usd_sum', caption: 'Sum in usd', size: '20%' }
    ],
    onRequest: function(data)
    {
        $('[data-export_table="grid_wallet_log"]').prop("href", Handlers.makeUrlExport("grid_wallet_log"));
        data.url = Handlers.makeUrl('grid_wallet_log');
    }
});

$('#sum').w2grid({
    name: 'logs_sum',
    method: 'GET',
    limit: 2,
    show: {
        footer    : true,
        toolbar    : false,
        toolbarReload: false
    },
    autoLoad: false,
    multiSearch: false
});

Handlers.initHandler();