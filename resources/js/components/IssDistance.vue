<template>
    <div class="card">
        <div class="card-header">
            <h1>Distance to ISS Calculator</h1>
        </div>
        <div class="card-body">

            <div v-if="errors.length" class="alert alert-danger">
                <b>Please correct the following error(s):</b>
                <ul>
                    <li v-for="error in errors">{{ error }}</li>
                </ul>
            </div>

            <form class="form-horizontal" role="form" @submit="checkForm">
                <div class="form-group">
                    <label for="latitude">Latitude</label>
                    <input type="text" v-model="latitude" class="form-control" id="latitude" required pattern="^-?([1-8]?[1-9]|[1-9]0)\.{1}\d{1,12}" aria-describedby="lathelp" placeholder="Enter Latitude">
                    <small id="lathelp" class="form-text text-muted">We'll need a value for Latitude.</small>
                </div>
                <div class="form-group">
                    <label for="longitude">Longitude</label>
                    <input type="text" v-model="longitude" class="form-control" id="longitude" required pattern="^-?([1]?[1-7][1-9]|[1]?[1-8][0]|[1-9]?[0-9])\.{1}\d{1,12}" aria-describedby="lonhelp" placeholder="Enter Longitude">
                    <small id="lonhelp" class="form-text text-muted">We'll need a value for Longitude.</small>
                </div>

                <button type="submit" class="btn btn-primary">Calculate Distance</button>
            </form>

            <div class="alert alert-info" v-if="distance && distance >= 0">
                <H2>Distance to ISS: {{ distance }} Km</H2>
            </div>

        </div>


    </div>
</template>

<script>
    export default {
        name: "IssDistance",
        data() {
            return {
                errors: [],
                latitude: null,
                longitude: null,
                distance: null
            }
        },
        props: {
            'issid': Number
        },
        mounted() {

        },
        methods: {

            checkForm: function (e) {
                this.distance = null;

                if (this.latitude && this.longitude) {
                    this.getDistance();
                }

                this.errors = [];

                if (!this.latitude) {
                    this.errors.push('Latitude is required.');
                }
                if (!this.longitude) {
                    this.errors.push('Longitude is required.');
                }

                e.preventDefault();
            },


            getDistance() {
                axios.get('/api/distance/' + this.latitude + ',' + this.longitude)
                    .then( (response) => {
                        if(response.data.result === 1) {
                            this.distance = response.data.data.distance.toFixed(3);
                        }
                        else {
                            this.errors.push(response.data.message);
                            this.distance = null;
                        }
                    } );

            }
        }
    }
</script>

<style scoped>

</style>
