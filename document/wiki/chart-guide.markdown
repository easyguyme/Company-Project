## Add dependency
To support echarts library lazy loading, all the dependency for wmCharts should be declared clearly. Every page which needs the chart directive should add wmCharts as dependency, example below:

```coffee
define [
   'wm/app'
   'wm/config'
   'core/directives/wmCharts'
   ...
```

## Line Chart
html example
```
<div wm-line-chart options="chart.lineChartOptions" width="100%" height="400px"></div>
<
```
data example:
```
{
    color: ["#57C6CD"， "#C490BF"],    // optional
    categories: ['Q1', 'Q2', 'Q3', 'Q4'],
    series: [
        {
          name: 'KLP Pull',
          data: [1.21, 1.25, 1.24, 1.3]
        }
        {
          name: 'KLP FT',
          data: [1.5, 1.45, 1.47, 1.50]
        }
    ],
    startDate: "2015-01-01",         //optional
    endDate: "2015-01-30"            //optional
}
```
## Horizontal Area Line Chart 
html example
```
<div wm-h-area-line-chart options="chart.hAreaLineChartOptions" width="50%" height="400px"></div>
```
data example
```
{
    color: ['#AFDB51', '#37C3AA', '#88C6FF', '#8660BB', '#F29C9F', '#FFBD5A', '#FACD89', '#F8E916'],
    title: "KLP Channel Penetration in Acct",
    series: [
        {value: 10009, name: 'T2'},
        {value: 30209, name: 'CR'},
        {value: 26209, name: 'Others'},
        {value: 2864, name: 'Canteen'},
        {value: 42193, name: 'Other LET'},
        {value: 35398, name: 'WR'},
        {value: 21859, name: 'Hotel'},
        {value: 18859, name: 'Food Factory'}
    ],
    startDate: "2015-01-01",         //optional
    endDate: "2015-01-30"            //optional
}
```
## Bar Chart
html example
```
<div wm-bar-chart options="chart.barChartOptions" width="100%" height="400px"></div>
```
data example
```
{
    color: ['#7E56B5', '#9374BE'],
    categories: ['2015-01', '2015-02', '2015-03', '2015-04', '2015-05', '2015-06'],
    stack: true,                //optional
    type: 'percent',            //optional
    series: [
        {
          name: 'Pull',
          data: [1800, 467, 233, 178, 678, 980]
        }
        {
          name: 'Free Trade',
          data: [515, 345, 662, 888, 432, 123]
        }
    ],
    startDate: "2015-01-01",    //optional
    endDate: "2015-01-30"       //optional
}
```
**stack** : means the bar is arranged side by side or stacked one by one

**type** : 'percent' means the label in the bar displays the percentage of the current bar data; if the type is not set, the label will display the current bar data

## Horizontal Bar Chart
html example
```
<div wm-h-bar-chart options="chart.hbarChartOptions" width="100%" height="200px"></div>
```
data example:
```
{
   color: ["#57C6CD"， "#C490BF"],    // String or Array
   categories: ["2015-01-01", "2015-01-02", "2015-01-03", "2015-01-04", "2015-01-05", "2015-01-06", "2015-01-07"],
   series: [{
       name: "人数",
       data: [11, 15, 35, 89, 90, 80, 10]
   },
   {
        name: "次数",
        data: [11, 15, 35, 89, 90, 80, 10]
   }],
   startDate: "2015-01-01",    //optional
   endDate: "2015-01-30"       //optional
}
```

## Accumulated Bar Chart
html example
```
<div wm-accumulated-bar-chart options="chart.accumulatedBarChartOptions" width="100%" height="400px"></div>
```
data example:
```
{
    color: ["#F86961", "#2DA4A8"],    // Array
    tooltipTitle: "累积粉丝数",
    categories: ["立顿", "多芬"],
    series: [[
                {name: "立顿茶", value: 1500},
                {name: "立顿上海活动群", value: 200}
            ],[
                {name: "多芬爱美丽", value: 1750},
                {name: "多芬活动", value: 200}
            ]
    ]
}
```

## Pie Chart
html example:
```
<div wm-pie-chart options="chart.pieChartOptions" width="100%" height="400px"></div>
```
data example:
```
{
    color: ['#AFDB51', '#37C3AA', '#88C6FF', '#8660BB', '#F29C9F', '#FFBD5A', '#FACD89', '#F8E916'],
    title: "KLP Channel Penetration in Volume",
    type: 'inner'        // 'inner' or 'outer', 'outer' is the default value
    series: [
        {value: 10009, name: 'T2'},
        {value: 70209, name: 'CR'},
        {value: 30209, name: 'Others'},
        {value: 2864, name: 'Canteen'},
        {value: 42193, name: 'Other LET'},
        {value: 35398, name: 'WR'},
        {value: 21859, name: 'Hotel'},
        {value: 18859, name: 'Food Factory'}
    ]
}
```

## Donut Chart
html example
```
<div wm-donut-chart options="chart.donutChartOptions" width="100%" height="400px"></div>
```
data example
```
{
    color: ["#19BE9B", "#88C6FF"],
    title: "Active and Inactive Acct Tracking",
    totalTitle: 'Active Acct',
    series: [
        {value: 1500, name: "NEW"},
        {value: 1720, name: "Current"},
    ]
}
```

## Map
html example
```
<div wm-map options="chart.mapOptions" width="100%" height="400px"></div>
```
data example:
```
{
    series: [
        {
            name: "iphone3",
            data: [
                {name: '北京',value: 100},
                {name: '天津',value: 200},
                {name: '上海',value: 300},
                {name: '重庆',value: 150},
                {name: '河北',value: 400},
                {name: '河南',value: 200}
            ]
        },
        {
            name: "iphone4",
            data: [
                {name: '北京',value: 600},
                {name: '天津',value: 300},
                {name: '上海',value: 300},
                {name: '重庆',value: 250},
                {name: '河北',value: 400},
                {name: '河南',value: 300}
            ]
        }
        {
            name: "iphone5",
            data: [
                {name: '北京',value: 700},
                {name: '天津',value: 300},
                {name: '上海',value: 300},
                {name: '重庆',value: 350},
                {name: '河北',value: 400},
                {name: '河南',value: 200}
            ]
        }
    ]
}
```
