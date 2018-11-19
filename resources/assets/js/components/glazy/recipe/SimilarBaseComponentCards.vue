<template>
    <div id="similar-base-components">
        <div class="load-container load7" v-if="isProcessing">
            <div class="loader">Searching...</div>
        </div>
        <div class="row" v-if="isLoaded && !isProcessing && materialList && materialList.length > 0">
            <div class="col-12 col-md-6 col-lg-4"
                 v-for="material in materialList"
                 @click="materialLink(material)">
                <div class="d-flex flex-row similar-base-card">
                    <div class="p-2">
                        <router-link :to="{ name: linkName(material), params: { id: material.id }}"
                                     class="nav-link">
                            <img
                                class="img img-raised img-similar rounded"
                                :src="glazyHelper.getSmallImageUrl(material, material.thumbnail)"
                                :alt="material.name"
                            />
                        </router-link>
                    </div>
                    <div class="p-2 flex-grow-1">
                        <h5 class="">
                            <router-link :to="{ name: linkName(material), params: { id: material.id }}">
                                <i v-if="material.isPrivate" class="fa fa-eye-slash"></i>
                                <i v-if="material.isArchived" class="fa fa-lock"></i>
                                {{ material.name }}
                            </router-link>
                        </h5>
                        <span class="badge badge-firing"
                              v-html="'&#9651;' + glazyHelper.getConeString(material) + ' ' + glazyHelper.getAtmospheresString(material)"></span>
                        <span v-if="material.materialStateId === 1" class="badge badge-warning">Test</span>
                        <span v-else-if="material.materialStateId === 3" class="badge badge-danger">Discontinued</span>
                        <router-link :to="{ name: 'user', params: { id: glazyHelper.getUserProfileUrlId(material.createdByUser) }}">
                            <div class="author">
                                <span>{{ glazyHelper.getUserDisplayName(material.createdByUser) }}</span>
                            </div>
                        </router-link>
                        <span v-html="getAdditionalComponentsString(material)"></span>
                    </div>
                </div>
            </div>
        </div>
        <div v-else>
            <h5 v-if="!isProcessing">No similar base recipes found.</h5>
        </div>
    </div>

</template>

<script>
import Vue from "vue";
import GlazyHelper from "../helpers/glazy-helper";

export default {
  name: "SimilarBaseComponentCards",
  props: ["material"],

  data() {
    return {
      materialList: null,
      glazyHelper: new GlazyHelper(),
      isLoaded: false,
      isProcessing: false
    };
  },

  computed: {},

  watch: {
    material: function(val) {
      this.fetchSimilarBaseComponents();
    }
  },

  mounted() {
    this.fetchSimilarBaseComponents();
  },

  methods: {
    fetchSimilarBaseComponents: function() {
      this.isProcessing = true;
      Vue.axios
        .get(
          Vue.axios.defaults.baseURL +
            "/search/similarBaseComponents/" +
            this.material.id
        )
        .then(response => {
          this.materialList = response.data.data;
          this.isLoaded = true;
          this.isProcessing = false;
        })
        .catch(response => {
          // Error Handling
          this.isProcessing = false;
        });
    },

    coneString: function(fromOrtonConeName, toOrtonConeName) {
      var coneString = "?";
      if (
        fromOrtonConeName &&
        toOrtonConeName &&
        fromOrtonConeName != toOrtonConeName
      ) {
        return fromOrtonConeName + "-" + toOrtonConeName;
      }

      if (fromOrtonConeName) {
        return (coneString = fromOrtonConeName);
      }

      if (toOrtonConeName) {
        coneString = toOrtonConeName;
      }
      return coneString;
    },

    getAdditionalComponentsString: function(similar) {
      var tmp = "";

      if (similar.materialComponents) {
        similar.materialComponents.forEach(component => {
          if (component.isAdditional) {
            if (tmp.length) {
              tmp += ", ";
            }
            tmp += this.glazyHelper.roundToTwo(component.percentageAmount) + ' ';
            tmp += component.material.name;
          }
        });
      }
      return tmp;
    },

    linkName: function (material) {
      if (material) {
        if (material.isPrimitive) {
          return 'material'
        }
        if (material.isAnalysis) {
          return 'analysis'
        }
        return 'recipes'
      }
    }
  }
};
</script>

<style>
    .similar-base-card h5 {
        font-size: 1.1em;
        line-height: 1.2em;
        margin-bottom: 5px;
    }

    .similar-base-card .img-similar {
        width: 120px !important;
        min-width: 120px !important;
        height: 120px !important;
    }

    .similar-base-card .badge-firing {
        color: #ffffff;
        background-color: #aaa;
    }
</style>