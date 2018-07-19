<template>
    <div id="collections-panel">
        <div class="row" v-if="collectionList.length">
            <div class="col-lg-6 col-md-6 col-sm-12 col-12"
                v-for="collection in collectionList"
                v-if="$auth.user().id === collection.createdByUser.id">
                <div class="card material-collection-card border bg-light">
                    <div class="card-body">
                        <h5 class="card-title">
                            <router-link :to="{ name: 'user', params: { id: glazyHelper.getUserProfileUrlId(collection.createdByUser) }, query: { collection: collection.id }}">
                                {{ collection.name }}
                                <span class="badge badge-collection">
                                    {{ collection.materialCount }}
                                </span>
                            </router-link>
                        </h5>
                        <router-link :to="{ name: 'user', params: { id: glazyHelper.getUserProfileUrlId(collection.createdByUser) }}">
                            <div class="author">
                                <img v-bind:src="glazyHelper.getUserAvatar(collection.createdByUser)"
                                     class="avatar"/>
                                <span>{{ glazyHelper.getUserDisplayName(collection.createdByUser) }}</span>
                            </div>
                        </router-link>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-4 col-sm-6 col-6"
                 v-for="collection in collectionList"
                 v-if="$auth.user().id !== collection.createdByUser.id">
                <div class="card material-collection-card border"
                     v-bind:class="{ 'bg-light': ($auth.user().id === collection.createdByUser.id) }"
                >
                    <div class="card-body">
                        <h5 class="card-title">
                            <router-link :to="{ name: 'user', params: { id: glazyHelper.getUserProfileUrlId(collection.createdByUser) }, query: { collection: collection.id }}">
                                {{ collection.name }}
                                <span class="badge badge-collection">
                                    {{ collection.materialCount }}
                                </span>
                            </router-link>
                        </h5>
                        <router-link :to="{ name: 'user', params: { id: glazyHelper.getUserProfileUrlId(collection.createdByUser) }}">
                            <div class="author">
                                <img v-bind:src="glazyHelper.getUserAvatar(collection.createdByUser)"
                                     class="avatar"/>
                                <span>{{ glazyHelper.getUserDisplayName(collection.createdByUser) }}</span>
                            </div>
                        </router-link>
                    </div>
                </div>
            </div>
        </div>
        <div class="collections-table" v-else>
            <h5>Not found in any collections</h5>
        </div>
    </div>
</template>
<script>
  import GlazyHelper from '../helpers/glazy-helper'

  export default {
    name: 'MaterialCollectionsPanel',
    props: {
      material: {
        type: Object,
        default: null
      },
    },
    data() {
      return {
        glazyHelper: new GlazyHelper()
      }
    },
    computed: {
      isLoaded: function () {
        if (this.material) {
          return true;
        }
        return false;
      },

      collectionList: function () {
        if (this.isLoaded) {
          if (this.material.hasOwnProperty('collections')) {
            return this.material.collections;
          }
        }
        return [];
      }

    }
  }

</script>

<style>

    .material-collection-card {

        -webkit-box-shadow: none;
        box-shadow: none;
        border: ;
    }
    .material-collection-card .card-title {
        margin-top: 0px;
    }

    .material-collection-card .card-body {
        padding: 10px;
    }

    .badge-collection {
        color: #ffffff;
        background-color: rgba(0,0,0,.2);
    }

</style>