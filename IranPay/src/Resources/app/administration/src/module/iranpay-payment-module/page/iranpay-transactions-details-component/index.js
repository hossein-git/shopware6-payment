import template from './transactions-list.html.twig';

const {Component} = Shopware;
const {Criteria} = Shopware.Data;

Component.register('iranpay-transactions-details-component', {
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
        // this.repository.search()

        this.repository
            .search(criteria, Shopware.Context.api)
            .then((result) => {
                this.transactions = result;
            });
    },

    methods: {

    }
});
