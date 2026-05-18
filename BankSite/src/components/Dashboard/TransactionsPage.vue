<script setup>
const props = defineProps({
    transactions: Object
});

const getAmountColor = (transaction) =>
    transaction.amount.startsWith("-")
        ? "text-red-600"
        : transaction.amount.startsWith("+")
            ? "text-green-600"
            : "";

const formatDate = (dateString) => {
    const date = new Date(dateString);
    let formatted =
        date.toLocaleString("en", { month: "long" }) + " " + date.getDate();

    if (date.getFullYear() < new Date().getFullYear()) {
        formatted += ` ${date.getFullYear()}`;
    }

    return formatted;
};

const byDateTime = (transactions) => {
    if (!transactions) {
        return {};
    }

    const sortedEntries = Object.entries(transactions).sort(
        ([dateA], [dateB]) => {
        return new Date(dateB) - new Date(dateA);
        },
    );

    return sortedEntries.reduce((acc, [date, transactionList]) => {
        const sortedTransactions = [...transactionList].sort((a, b) => {
        const dateTimeA = new Date(`${date} ${a.time}`);
        const dateTimeB = new Date(`${date} ${b.time}`);
        return dateTimeB - dateTimeA;
        });

        acc[date] = sortedTransactions;
        return acc;
    }, {});
};
</script>

<template>
    <div class="dashboard-transactions">
        <div v-for="(transactionsForDate, date) in byDateTime(props.transactions)" :key="date" class="w-full">
            <p class="text-2xl font-bold mb-4">{{ formatDate(date) }}</p>
            <div v-for="(transaction, index) in transactionsForDate" :key="index"
                class="flex w-full h-fit bg-white p-4 justify-between rounded-2xl shadow-md mb-6">

                <div class="flex flex-col gap-2">
                    <p class="text-xl font-semibold">{{ transaction.name }}</p>
                    <p class="text-gray-600 italic">{{ transaction.type }}</p>
                </div>
                <div class="flex flex-col gap-2 text-right">
                    <p class="text-xl font-semibold" :class="getAmountColor(transaction)">
                        {{ transaction.amount }}
                    </p>
                    <p class="text-gray-600 italic">{{ transaction.card_type }}</p>
                </div>
            </div>
        </div>
    </div>
</template>