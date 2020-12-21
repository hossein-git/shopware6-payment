import './page/components/iranpay-transactions-list';
import './page/iranpay-transactions-list-component';

import enGB from './snippet/en-GB.json';

const {Module} = Shopware;

Module.register('iranpay-payment-module', {
    type: "plugin",
    name: "module.name",
    title: "module.title",
    description: "module.description",
    color: '#23ac70',
    snippets: {
        'en-GB': enGB,
    },
    routes: {
        list: {
            component: 'iranpay-transactions-list-component',
            path: 'list'
        },
        // details: {
        //     component: 'iranpay-transactions-details-component',
        //     path: 'details'
        // }
    },
    navigation: [
        {
            parent: 'sw-order',
            label: "module.navigation.label",
            path: 'iranpay.payment.module.list',
        },
        // {
        //     parent: 'sw-order',
        //     label: "module.navigation.label",
        //     path: 'iranpay.payment.module.details',
        // }
    ]
});
