<template>
    <b-card
        v-if="isLoaded"
        @mouseover="highlightMaterial(material.id)"
        @mouseleave="unhighlightMaterial(material.id)"
        class="edit-recipe-components-card">

        <div class="d-flex mb-2">
            <div class="flex-grow-1 pr-2">
                <router-link :to="{ name: 'material', params: { id: material.id }}" class="">
                    {{ this.material.name }}
                </router-link>
            </div>
        </div>
        <div v-if="hasUmf && displayType === 'umf'" class="d-flex">
            <div>
                <umf-traditional-notation
                        :umfAnalysis="umfAnalysis"
                        :showSimpleLegend="true"
                        size="m">
                </umf-traditional-notation>
            </div>
        </div>
        <div v-else-if="displayType === 'umf'" class="d-flex">
            UMF not possible because no fluxes to unify.
            Please choose Mol Percent or Percentage analysis.
        </div>
        <div v-if="hasUmf && displayType === 'umf'" class="d-flex">
            <div class="ratios">
                R<sub>2</sub>O:RO
                <span class="badge">
                    <span class="oxide-colors-Na2O">
                        {{ Number(umfAnalysis.getR2OTotal()).toFixed(2) }}
                    </span>
                    :
                    <span class="oxide-colors-CaO">
                        {{ Number(umfAnalysis.getROTotal()).toFixed(2) }}
                    </span>
                </span>
            </div>
            <div class="ratios ml-2">
                SiO<sub>2</sub>:Al<sub>2</sub>O<sub>3</sub>
                <span class="badge">
                    {{ Number(umfAnalysis.getSiO2Al2O3Ratio()).toFixed(2) }}
                </span>
            </div>
        </div>
        <div v-if="displayType === 'percentMol'"
             class="table-responsive">
            <component-table
                    :material="material"
                    :isFormula="true">
            </component-table>
        </div>
        <div v-if="displayType === 'percent'"
             class="table-responsive">
            <component-table
                    :material="material"
                    :isFormula="false">
            </component-table>
        </div>

        <div class="d-flex mt-2">
            <b-button-toolbar>
                <b-button-group size="sm" class="mx-1">
                    <b-button v-if="canRemove"
                              variant="neutral"
                              @click.prevent="cancelRecipeCard"><i class="fa fa-times"></i> Remove</b-button>
                </b-button-group>
            </b-button-toolbar>
        </div>
    </b-card>
</template>

<script>
  import UmfTraditionalNotation from '../analysis/UmfTraditionalNotation.vue'
  import ComponentTable from '../analysis/ComponentTable.vue'

  export default {
    name: 'EditRecipeCard',
    components: {
      UmfTraditionalNotation,
      ComponentTable
    },
    props: {
      material: {
        type: Object,
        default: null
      },
      displayType: {
        type: String,
        default: 'umf'
      },
      canRemove: {
        type: Boolean,
        default: true
      }
    },
    data() {
      return {
      }
    },

    mounted() {
    },

    computed : {
      umfAnalysis: function() {
        if (this.material) {
          return this.material.getROR2OUnityFormulaAnalysis();
        }
        return null;
      },
      hasUmf: function () {
        if (this.umfAnalysis &&
          (this.umfAnalysis.getR2OTotal() > 0 ||
          this.umfAnalysis.getROTotal() > 0)) {
          return true
        }
        return false
      }
    },
    methods: {

      isLoaded: function() {
        if (this.material) {
          return true;
        }
        return false;
      },

      cancelRecipeCard: function() {
        this.$emit('cancelRecipeCard');
      },

      highlightMaterial: function (id) {
        this.$emit('highlightMaterial', id);
      },

      unhighlightMaterial: function (id) {
        this.$emit('unhighlightMaterial', id);
      }

    }
  }
</script>

<style>
    .btn-toolbar .btn-group .btn {
        margin: 0;
    }

    .edit-recipe-components-card .btn-toolbar {
        margin: 0;

    }
    .edit-recipe-components-card .card-body {
        padding: 10px 10px;
    }
    .edit-recipe-components-card .card-body .ratios {
        margin-top: 4px;
        font-size: 14px;
    }
    .edit-recipe-components-card .card-body .ratios .badge {
        background-color: #efefef;
        font-size: 14px;
        padding: 4px;
        margin: 2px;
    }
    .edit-recipe-components-card .umf-traditional {
        margin: 0;
        font-size: 14px;
        line-height: 18px;
    }
    .edit-recipe-components-card .umf-traditional thead tr th {
        font-size: 12px;
    }
    .edit-recipe-components-card .umf-traditional tbody tr td.bracket {
        padding: 0 4px;
    }

</style>