<template>
    <div class="card">
        <div class="card-header">
            <h1>ISS Info</h1>
        </div>
        <div class="table-responsive-sm" v-if="havedetails">
            <table class="table ">
                <tr>
                    <td><b>Latitude:</b></td>
                    <td>{{ info.latitude }}</td>
                </tr>
                <tr>
                    <td><b>Longitude:</b></td>
                    <td>{{ info.longitude }}</td>
                </tr>
                <tr>
                    <td><b>Velocity:</b></td>
                    <td>{{ info.velocity}} Km/h</td>
                </tr>
                <tr>
                    <td><b>Timestamp:</b></td>
                    <td>{{ info.timestamp}}</td>
                </tr>
                <tr>
                    <td><b>Time:</b></td>
                    <td>{{ formatTimestamp(info.timestamp) }}</td>
                </tr>
                <tr>
                    <td colspan="2"><b>{{ info.latitude}},{{ info.longitude}}</b></td>
                </tr>
            </table>
        </div>
        <div class="card-body" v-else>
            <span>Transmitting ...</span>
        </div>
        <div class="card-footer text-center" v-if="havedetails">
            <button type="button" class="btn btn-info" v-on:click="getIssInfo()" id="refresh">Refresh</button>
        </div>
    </div>
</template>

<script>
    export default {
        name: "IssPosition",
        data() {
            return {
                info: {},
                havedetails: false
            }
        },
        props: {
            'issid': Number
        },
        mounted() {
            if(this.havedetails === false) {
                this.getIssInfo();
            }
        },
        methods: {
            formatTimestamp(timestamp) {
                let d = new Date(0);
                d.setTime(timestamp * 1000);
                return d;
            },

            getIssInfo() {
                if(this.havedetails !== false) this.havedetails = false;
                axios.get('/api/satellite/' + this.issid)
                    .then( (response) => {
                        if(response.data.result === 1) {
                            this.info = response.data.data;
                            this.havedetails = true
                        }
                    } );
            }
        }
    }
</script>

<style scoped>

</style>
