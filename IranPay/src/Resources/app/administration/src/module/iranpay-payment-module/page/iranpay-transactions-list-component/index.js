import template from './transactions-list.html.twig';

const {Component} = Shopware;
const {Criteria} = Shopware.Data;

Component.register('iranpay-transactions-list-component', {
    template,

    inject: [
        'repositoryFactory',
        'stateStyleDataProviderService'
    ],

    data() {
        return {
            repository: null,
            transactions: null,
            showExModal :false
        };
    },

    metaInfo() {
        return {
            title: this.$createTitle()
        };
    },

    computed: {
        columns() {
            return [
                {
                    property: 'createdAt',
                    dataIndex: 'createdAt',
                    label: this.$t('transactions-list.created_at'),
                    sortIsAllowed: true,
                    allowResize: true,
                },
                {
                    property: 'updatedAt',
                    dataIndex: 'updatedAt',
                    label: this.$t('transactions-list.updated_at'),
                    sortIsAllowed: true,
                    allowResize: true,
                },
                {
                    property: 'paymentMethod',
                    dataIndex: 'paymentMethod',
                    label: 'Method Name',
                    allowResize: true,
                    sortIsAllowed: true,
                    primary: true
                },
                {
                    property: 'iranpayTransactionId',
                    dataIndex: 'iranpayTransactionId',
                    label: this.$t('transactions-list.iranpay_transaction_id'),
                    allowResize: true,
                    sortIsAllowed: true,
                    primary: true
                },
                {
                    property: 'order.orderNumber',
                    dataIndex: 'orderNumber',
                    label: this.$t('transactions-list.order_number'),
                    sortIsAllowed: false,
                    allowResize: true,
                },
                {
                    property: 'amount',
                    dataIndex: 'amount',
                    label: this.$t('transactions-list.amount'),
                    allowResize: true,
                    sortIsAllowed: true,
                    primary: true
                },
                {
                    property: 'customer.lastName',
                    dataIndex: 'customer',
                    label: this.$t('transactions-list.customer'),
                    allowResize: true,
                    sortIsAllowed: false,
                    primary: true
                },
                {
                    property: 'status',
                    dataIndex: 'status',
                    label: this.$t('transactions-list.status'),
                    allowResize: true,
                    sortIsAllowed: false,
                    primary: true
                },
                {
                    property: 'n.status',
                    dataIndex: 'n.status',
                    label: 'Status',
                    allowResize: true,
                    sortIsAllowed: false,
                    primary: true
                },
                {
                    property: 'exception',
                    dataIndex: 'exception',
                    label: 'ERROR',
                    allowResize: true,
                    sortIsAllowed: false,
                    primary: true
                },
            ];
        }
    },

    created() {
        this.repository = this.repositoryFactory.create('iranpay_transactions');
        let criteria = new Criteria();
        criteria.addAssociation('order');
        criteria.addAssociation('customer');
        criteria.addAssociation('stateMachineState');
        criteria.addSorting(
            Criteria.sort('iranpay_transactions.createdAt', 'DESC')
        );

        this.repository
            .search(criteria, Shopware.Context.api)
            .then((result) => {
                this.transactions = result;
            });
    },

    methods: {
        openModal(){
            this.showExModal = true
        },
        getVariantFromPaymentState(technicalName) {
            return this.stateStyleDataProviderService.getStyle(
                'order_transaction.state', technicalName
            ).variant;
        },

        getData(date) {
            if (date <= 0) {
                return '';
            }

            let regex = /(?<year>\d{4}).(?<month>\d{2}).(?<day>\d{2}).(?<hours>\d{2}).(?<minutes>\d{2})/gm; //NOSONAR
            let dateGroup = regex.exec(date)['groups'];

            return dateGroup['year'] + '-' + dateGroup['month'] + '-' + dateGroup['day'] + ' ' + dateGroup['hours'] + ':' + dateGroup['minutes'];
        }
    }
});
