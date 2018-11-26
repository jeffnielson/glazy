<template>
    <div class="table-responsive">
        <table v-if="isLoaded"
               class="table table-hover material-recipe-calculator-table">
            <thead>
            <tr>
                <th>Material</th>
                <th class="text-right">Amount</th>
                <th v-if="batchValues" class="text-right">Batch</th>
                <th v-if="batchValues && isCumulative === 'true'" class="text-right">Subtotal</th>
                <th v-else-if="batchValues && tareSize" class="text-right">Batch+Tare</th>
            </tr>
            </thead>
            <tbody>
            <tr v-if="batchValues && tareSize"
                class="table-warning">
                <td>Tare Weight</td>
                <td class="text-right batch"
                    id="batch_tare">
                    {{ parseFloat(tareSize) }}
                </td>
                <td></td>
                <td v-if="batchValues && isCumulative === 'true'"
                    class="text-right subtotal"
                    id="subtotal_tare">
                    {{ parseFloat(tareSize) }}
                </td>
                <td v-else></td>
            </tr>
            <tr v-for="(materialComponent, index) in this.materialComponents"
                v-if="!materialComponent.isAdditional">
                <td class="align-middle">
                    <div v-if="!isPrint"
                         class="d-none d-none d-sm-block float-left">
                        <img class="rounded-circle mr-1"
                             width="34" height="34"
                             v-if="materialComponent.material.thumbnail"
                             :src="glazyHelper.getSmallImageUrl(materialComponent.material, materialComponent.material.thumbnail)"/>
                    </div>
                    <div v-if="isPrint">
                        {{ materialComponent.material.name }}
                        <span v-if="materialComponent.material.materialStateId === 1" >(Test)</span>
                        <span v-else-if="materialComponent.material.materialStateId === 3">(Discontinued)</span>
                    </div>
                    <div v-else>
                        <router-link v-if="materialComponent.material.isPrimitive" :to="{ name: 'material', params: { id: materialComponent.material.id }}">{{ materialComponent.material.name }}</router-link>
                        <router-link v-else :to="{ name: 'recipes', params: { id: materialComponent.material.id }}">{{ materialComponent.material.name }}</router-link>
                        <span v-if="materialComponent.material.materialStateId === 1" class="badge badge-warning">Test</span>
                        <span v-else-if="materialComponent.material.materialStateId === 3" class="badge badge-danger">Discontinued</span>
                    </div>
                </td>
                <td class="text-right amount">
                    {{ parseFloat(materialComponent.percentageAmount) }}
                </td>
                <td v-if="batchValues"
                    class="text-right"
                    v-bind:class="isPrint ? '' : 'batch'"
                    :id="'batch_' + materialComponent.material.id">
                    {{ parseFloat(batchValues.batchRows[index]) }}
                </td>
                <td v-if="batchValues && isCumulative === 'true'"
                    class="text-right"
                    v-bind:class="isPrint ? '' : 'subtotal'"
                    :id="'subtotal_' + materialComponent.material.id">
                    {{ parseFloat(batchValues.subtotalRows[index]) }}
                </td>
                <td v-else-if="batchValues && tareSize"
                    class="text-right subtotal" :id="'batch_tare_' + materialComponent.material.id">
                    {{ parseFloat(batchValues.batchTareRows[index]) }}
                </td>
            </tr>
            <tr v-if="hasAdditional"
                v-bind:class="isPrint ? '' : 'total'"
                class="align-middle">
                <td>Total Base Recipe</td>
                <td class="text-right">{{ parseFloat(baseRecipeAmount) }}</td>
                <td v-if="batchValues" class="text-right">{{ parseFloat(this.batchSize.toFixed(2)) }}</td>
                <td v-if="batchValues && (isCumulative === 'true' || tareSize)"></td>
            </tr>

            <tr v-for="(materialComponent, index) in this.materialComponents"
                v-if="materialComponent.isAdditional"
                v-bind:class="isPrint ? '' : 'table-info'">
                <td class="align-middle">
                    <div v-if="!isPrint"
                         class="d-inline d-none d-sm-block float-left">
                        <img class="rounded-circle mr-1"
                             width="34" height="34"
                             v-if="materialComponent.material.thumbnail"
                             :src="glazyHelper.getSmallImageUrl(materialComponent.material, materialComponent.material.thumbnail)"/>
                    </div>
                    <div v-if="isPrint">
                        <i class="fa fa-plus"></i>
                        {{ materialComponent.material.name }}
                    </div>
                    <div v-else>
                        <i class="fa fa-plus"></i>
                        <router-link v-if="materialComponent.material.isPrimitive" :to="{ name: 'material', params: { id: materialComponent.material.id }}">{{ materialComponent.material.name }}</router-link>
                        <router-link v-else :to="{ name: 'recipes', params: { id: materialComponent.material.id }}">{{ materialComponent.material.name }}</router-link>
                    </div>
                </td>
                <td class="text-right amount">
                    {{ parseFloat(materialComponent.percentageAmount) }}
                </td>
                <td v-if="batchValues"
                    class="text-right"
                    v-bind:class="isPrint ? '' : 'batch'"
                    :id="'batch_' + materialComponent.material.id">
                    {{ parseFloat(batchValues.batchRows[index]) }}
                </td>
                <td v-if="batchValues && isCumulative === 'true'"
                    class="text-right"
                    v-bind:class="isPrint ? '' : 'subtotal'"
                    :id="'subtotal_' + materialComponent.material.id">
                    {{ parseFloat(batchValues.subtotalRows[index]) }}
                </td>
                <td v-else-if="batchValues && tareSize"
                    class="text-right batch" :id="'batch_tare_' + materialComponent.material.id">
                    {{ parseFloat(batchValues.batchTareRows[index]) }}
                </td>
            </tr>

            <tr class="align-middle" v-bind:class="isPrint ? '' : 'total'">
                <td>Total</td>
                <td class="text-right">{{ parseFloat(totalRecipeAmount) }}</td>
                <td v-if="batchValues" class="text-right">{{ parseFloat(totalBatchAmount) }}</td>
                <td v-if="batchValues && (isCumulative === 'true' || tareSize)"></td>
            </tr>

            <tr class="batch_form" v-if="!isPrint">
                <td v-bind:colspan="numColumns">
                    <button class="btn btn-sm btn-default btn-print"
                            @click="openPrintView()">
                        <i class="fa fa-print"></i> Print
                    </button>
                    <form class="form-inline float-right mt-1">
                        <div class="form-group">
                            <select v-model="isCumulative"
                                    class="form-control form-control-sm">
                                <option value="true">Weigh together</option>
                                <option value="false">Weigh separately</option>
                            </select>
                        </div>
                    </form>
                    <form class="form-inline batch-form-inline float-right mt-1 mr-2">
                        <div class="form-group">
                            <label class="form-label form-label-sm mr-1" for="tareSize">Tare:</label>
                            <input type="number"
                                   inputmode="numeric"
                                   size="4"
                                   maxlength="10"
                                   placeholder="0.0"
                                   id="tareSize"
                                   class="form-control form-control-sm material-recipe-calculator-tare-input mr-2"
                                   v-model.number="tareSize">
                        </div>
                        <div class="form-group">
                            <label class="form-label form-label-sm mr-1" for="batchSize">Batch:</label>
                            <input type="number"
                                   inputmode="numeric"
                                   size="8"
                                   maxlength="10"
                                   placeholder="0.0"
                                   id="batchSize"
                                   class="form-control form-control-sm material-recipe-calculator-batch-input"
                                   v-model.number="batchSize">
                        </div>
                    </form>
                </td>
            </tr>

            </tbody>
        </table>
    </div>
</template>

<script>
import GlazyHelper from '../helpers/glazy-helper'

export default {
  name: 'MaterialRecipeCalculator',

  props: {
    materialComponents: {
      type: Array,
      default: null
    },
    isPrint: {
      type: Boolean,
      default: false
    },
    initialBatchSize: {
      type: String,
      default: null
    }
  },

  data() {
    return {
      glazyHelper: new GlazyHelper(),
      batchSize: null,
      tareSize: null,
      isCumulative: "true"
    }
  },
  mounted() {
    if (this.initialBatchSize) {
      this.batchSize = Number(this.initialBatchSize);
    }
  },
  computed: {
    isLoaded: function () {
      if (this.materialComponents) {
        return true;
      }
      return false;
    },
    baseRecipeAmount: function () {
      var baseRecipeAmount = 0.0
      if (this.materialComponents && this.materialComponents.length > 0) {
        this.materialComponents.forEach(function (materialComponent, index) {
          if (!materialComponent.isAdditional) {
            baseRecipeAmount += parseFloat(materialComponent.percentageAmount);
          }
        })
        return baseRecipeAmount.toFixed(2)
      }
      return 0
    },
    totalRecipeAmount: function () {
      var totalRecipeAmount = 0.0
      if (this.materialComponents && this.materialComponents.length > 0) {
        this.materialComponents.forEach(function (materialComponent, index) {
          totalRecipeAmount += parseFloat(materialComponent.percentageAmount);
        })
        return totalRecipeAmount.toFixed(2)
      }
      return 0
    },
    totalBatchAmount: function () {
      if (!this.batchSize) {
        return 0;
      }
      return (this.totalRecipeAmount * this.batchSize / this.baseRecipeAmount).toFixed(2);
    },
    batchValues: function () {
      if (this.batchSize &&
        !isNaN(parseFloat(this.batchSize)) &&
        this.materialComponents) {
        var batchValues = {
          batchRows: [],
          batchTareRows: [],
          subtotalRows: []
        }
        var subtotal = 0;
        if (this.tareSize && parseFloat(this.tareSize) > 0) {
          subtotal += parseFloat(this.tareSize)
        }
        this.materialComponents.forEach(function (materialComponent, index) {
          var value = parseFloat(materialComponent.percentageAmount)
            * parseFloat(this.batchSize)
            / parseFloat(this.baseRecipeAmount)
          subtotal += value
          batchValues.batchRows[index] = value.toFixed(2)
          if (this.tareSize && parseFloat(this.tareSize) > 0) {
            batchValues.batchTareRows[index] = (value + this.tareSize).toFixed(2);
          }
          batchValues.subtotalRows[index] = subtotal.toFixed(2)
        }.bind(this))
        return batchValues
      }
      return null
    },
    numColumns: function () {
      if (this.batchValues) {
        return 4
      }
      return 2
    },
    hasAdditional: function () {
      var hasAdditional = false
      this.materialComponents.forEach(function (materialComponent, index) {
        if (materialComponent.isAdditional) {
          hasAdditional = true
        }
      })
      return hasAdditional
    },
    openPrintView: function () {
      this.$router.push({ name: 'recipe-print', params: { amount: this.batchSize }})
    }
  }
}

</script>

<style>

    .material-recipe-calculator-table {
        font-size: 1em;
    }

    .material-recipe-calculator-table tr th {
        font-size: 0.75em;
        padding: 5px;
    }

    .material-recipe-calculator-table tr td {
        padding: 5px;
        line-height: 34px;
        min-height: 34px;
        height: 34px;
    }

    .material-recipe-calculator-table tr td.batch {
        color: #009900;
    }

    .material-recipe-calculator-table tr td.subtotal {
        color: #000099;
    }

    .material-recipe-calculator-table tr.total {
        background-color: #efefef;
        font-size: 0.825em;
        font-style: italic;
    }

    .material-recipe-calculator-table tr.total td {
        line-height: 18px;
        min-height: 18px;
        height: 18px;
    }

    .batch-form-inline .form-control{
        width: 90px;
    }

    .btn-print {
        padding: 6px 8px;
        margin-top: 4px;
    }

</style>